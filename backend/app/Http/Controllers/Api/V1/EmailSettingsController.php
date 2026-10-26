<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailSettingsRequest;
use App\Http\Requests\SendTestEmailRequest;
use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailSettingsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        $settings = EmailSetting::query()->first();

        return response()->json(['data' => $settings ? [
            'host' => $settings->host,
            'port' => $settings->port,
            'username' => $settings->username,
            'scheme' => $settings->scheme,
            'from_address' => $settings->from_address,
            'from_name' => $settings->from_name,
            'password_configured' => filled($settings->password),
        ] : null]);
    }

    public function update(EmailSettingsRequest $request)
    {
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $settings = EmailSetting::query()->first() ?? new EmailSetting;
        $settings->fill($data)->save();

        return response()->json(['message' => 'Email settings saved. The SMTP password is encrypted at rest.', 'data' => [
            'host' => $settings->host,
            'port' => $settings->port,
            'username' => $settings->username,
            'scheme' => $settings->scheme,
            'from_address' => $settings->from_address,
            'from_name' => $settings->from_name,
            'password_configured' => filled($settings->password),
        ]]);
    }

    public function test(SendTestEmailRequest $request)
    {
        $settings = EmailSetting::query()->first();
        if (! $settings || blank($settings->host)) {
            throw ValidationException::withMessages(['email' => 'Save the SMTP settings before sending a test email.']);
        }

        try {
            Mail::raw('This is a test message from the ZomZom CRM email settings.', function ($message) use ($request): void {
                $message->to($request->validated('email'))->subject('ZomZom CRM email configuration test');
            });
        } catch (\Throwable $exception) {
            Log::error('CRM SMTP test email failed.', ['exception' => $exception::class]);
            throw ValidationException::withMessages(['email' => 'The test email could not be sent. Check your SMTP details and backend logs.']);
        }

        return response()->json(['message' => 'Test email sent. Check the recipient inbox.']);
    }
}
