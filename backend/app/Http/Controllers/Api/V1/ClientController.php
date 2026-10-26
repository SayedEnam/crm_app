<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Client::class);
        $q = Client::visibleTo($request->user())->withExists('portalAccount');
        if ($search = $request->string('search')->toString()) {
            $q->where(fn ($x) => $x->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%")->orWhere('phone', 'like', "%$search%"));
        } if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return ClientResource::collection($q->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(ClientRequest $request)
    {
        Gate::authorize('create', Client::class);
        $data = $request->validated();
        $data['assigned_user_id'] = $this->assignee($request->user(), $data['assigned_user_id'] ?? null);

        return (new ClientResource(Client::create($data)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Client $client)
    {
        Gate::authorize('view', $client);

        return new ClientResource($client);
    }

    public function update(ClientRequest $request, Client $client)
    {
        Gate::authorize('update', $client);
        $data = $request->validated();
        if (array_key_exists('assigned_user_id', $data)) {
            $data['assigned_user_id'] = $this->assignee($request->user(), $data['assigned_user_id']);
        } $client->update($data);

        return new ClientResource($client->refresh());
    }

    public function destroy(Request $request, Client $client)
    {
        Gate::authorize('delete', $client);
        $client->delete();

        return response()->noContent();
    }

    private function assignee(User $user, ?int $id): int
    {
        $id ??= $user->id;
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            abort_unless(User::whereKey($id)->exists(), 422);
        } elseif ($user->hasRole('manager')) {
            abort_unless(User::whereKey($id)->where(fn ($q) => $q->whereKey($user->id)->orWhere('assigned_manager_id', $user->id))->exists(), 403);
        } else {
            abort_unless($id === $user->id, 403);
        }

        return $id;
    }
}
