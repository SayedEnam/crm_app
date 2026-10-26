<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalRegistrationRequest;
use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\ClientPortalAccount;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ClientPortalController extends Controller
{
    public function register(ClientPortalRegistrationRequest $request)
    {
        $data = $request->validated();
        if (Client::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'A client record already uses this email. Ask your staff contact to send you a portal invitation.']);
        }

        $account = DB::transaction(function () use ($data): ClientPortalAccount {
            $ownerId = User::query()->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'super-admin', 'manager']))
                ->value('id');
            $client = Client::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'country' => $data['country'] ?? null, 'status' => 'lead', 'assigned_user_id' => $ownerId]);

            return ClientPortalAccount::create(['client_id' => $client->id, 'name' => $client->name, 'email' => $client->email, 'password' => $data['password'], 'is_active' => true]);
        });

        Auth::guard('client_portal')->login($account);
        $request->session()->regenerate();

        return response()->json(['user' => $this->portalUser($account->load('client'))], 201);
    }

    public function invite(Request $request, Client $client)
    {
        Gate::authorize('update', $client);
        abort_unless($request->user()->hasPermission('clients.manage'), 403);
        if (! $client->email) {
            throw ValidationException::withMessages(['email' => 'Add an email address to this client before inviting them.']);
        }

        $existing = ClientPortalAccount::where('email', $client->email)->first();
        if ($existing && $existing->client_id !== $client->id) {
            throw ValidationException::withMessages(['email' => 'This email address is already linked to another client portal.']);
        }

        $account = ClientPortalAccount::firstOrNew(['client_id' => $client->id]);
        $isNewAccount = ! $account->exists;
        $account->fill(['name' => $client->name, 'email' => $client->email, 'is_active' => true]);
        if ($isNewAccount) {
            $account->password = Hash::make(Str::random(48));
        }
        $account->save();

        try {
            $status = Password::broker('client_portal')->sendResetLink(['email' => $account->email]);
        } catch (\Throwable $exception) {
            Log::error('Client portal account created but setup email could not be sent.', ['client_id' => $client->id, 'exception' => $exception::class]);

            return response()->json(['account_created' => true, 'invitation_sent' => false, 'message' => 'Portal account created, but the setup email could not be sent. Check the mail configuration and retry.'], 201);
        }

        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['account_created' => true, 'invitation_sent' => true, 'message' => 'Portal account is ready. A setup link was sent recently; wait one minute before resending.']);
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json(['account_created' => true, 'invitation_sent' => false, 'message' => 'Portal account created, but the setup link could not be generated.'], 201);
        }

        return response()->json(['account_created' => true, 'invitation_sent' => true, 'message' => 'Portal account created and password setup link sent to '.$account->email.'.'], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['sometimes', 'boolean']]);
        $account = ClientPortalAccount::where('email', $credentials['email'])->first();
        if (! $account || ! $account->is_active || ! Auth::guard('client_portal')->attempt(['email' => $credentials['email'], 'password' => $credentials['password']], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $this->portalUser($account->load('client'))]);
    }

    public function logout(Request $request)
    {
        Auth::guard('client_portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request)
    {
        return response()->json($this->portalUser($request->user()->load('client')));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::broker('client_portal')->sendResetLink($request->only('email'));

        return response()->json(['message' => $status === Password::RESET_LINK_SENT ? __($status) : 'If an active client portal account exists, a setup link will be sent.']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(12)]]);
        $status = Password::broker('client_portal')->reset($data, function (ClientPortalAccount $account, string $password): void {
            $account->forceFill(['password' => Hash::make($password), 'is_active' => true])->setRememberToken(Str::random(60));
            $account->save();
            event(new PasswordReset($account));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => __($status)]);
    }

    public function dashboard(Request $request)
    {
        /** @var ClientPortalAccount $account */
        $account = $request->user();
        $client = $account->client;

        return response()->json([
            'client' => ['id' => $client->id, 'name' => $client->name, 'email' => $client->email, 'phone' => $client->phone, 'country' => $client->country, 'address' => $client->address],
            'applications' => ApplicationRecord::with('service:id,name')->where('client_id', $client->id)->latest()->get(['id', 'service_id', 'reference', 'status', 'started_at', 'completed_at', 'created_at']),
            'documents' => Document::whereHas('application', fn ($query) => $query->where('client_id', $client->id))->with('application:id,reference')->latest()->get(['id', 'application_id', 'name', 'mime_type', 'status', 'created_at'])->map(fn (Document $document) => ['id' => $document->id, 'application_id' => $document->application_id, 'application_reference' => $document->application?->reference, 'name' => $document->name, 'mime_type' => $document->mime_type, 'status' => $document->status, 'created_at' => $document->created_at]),
            'follow_ups' => FollowUp::where('client_id', $client->id)->where('status', 'pending')->where('due_at', '>=', now()->startOfDay())->orderBy('due_at')->get(['id', 'application_id', 'due_at', 'status']),
            'payments' => Payment::where('client_id', $client->id)->latest()->get(['id', 'application_id', 'amount', 'currency', 'status', 'paid_at', 'reference']),
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $data = $request->validate(['application_id' => ['required', 'integer', 'exists:applications,id'], 'category' => ['required', 'in:certificate,marksheet,other'], 'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg', 'max:10240']]);
        /** @var ClientPortalAccount $account */
        $account = $request->user();
        $application = ApplicationRecord::where('client_id', $account->client_id)->findOrFail($data['application_id']);
        $file = $request->file('file');
        $path = $file->store('client-documents', 'local');
        try {
            $document = $application->documents()->create(['uploaded_by' => null, 'name' => $data['category'].': '.$file->getClientOriginalName(), 'file_path' => $path, 'mime_type' => $file->getMimeType(), 'status' => 'pending', 'notes' => 'Uploaded by client portal.']);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['message' => 'Document uploaded for review.', 'document' => ['id' => $document->id, 'name' => $document->name, 'status' => $document->status]], 201);
    }

    public function downloadDocument(Request $request, Document $document)
    {
        /** @var ClientPortalAccount $account */
        $account = $request->user();
        abort_unless($document->application()->where('client_id', $account->client_id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->name);
    }

    private function portalUser(ClientPortalAccount $account): array
    {
        return ['id' => $account->id, 'name' => $account->name, 'email' => $account->email, 'client' => ['id' => $account->client->id, 'name' => $account->client->name, 'email' => $account->client->email, 'phone' => $account->client->phone, 'address' => $account->client->address]];
    }
}
