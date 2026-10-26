<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FollowUpRequest;
use App\Http\Resources\FollowUpResource;
use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', FollowUp::class);
        $q = FollowUp::visibleTo($request->user())->with('client:id,name');
        if ($request->query('due')) {
            $q->whereDate('due_at', $request->query('due'));
        }

return FollowUpResource::collection($q->orderBy('due_at')->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(FollowUpRequest $request)
    {
        Gate::authorize('create', FollowUp::class);
        $data = $request->validated();
        Client::visibleTo($request->user())->findOrFail($data['client_id']);
        if (! empty($data['application_id'])) {
            ApplicationRecord::visibleTo($request->user())->findOrFail($data['application_id']);
        } $data['assigned_user_id'] = $this->assignee($request->user(), $data['assigned_user_id'] ?? null);
        $row = FollowUp::create($data);

        return (new FollowUpResource($row->load('client')))->response()->setStatusCode(201);
    }

    public function show(Request $request, FollowUp $followUp)
    {
        Gate::authorize('view', $followUp);

        return new FollowUpResource($followUp->load('client'));
    }

    public function update(FollowUpRequest $request, FollowUp $followUp)
    {
        Gate::authorize('update', $followUp);
        $data = $request->validated();
        Client::visibleTo($request->user())->findOrFail($data['client_id']);
        if (! empty($data['application_id'])) {
            ApplicationRecord::visibleTo($request->user())->findOrFail($data['application_id']);
        } if (array_key_exists('assigned_user_id', $data)) {
            $data['assigned_user_id'] = $this->assignee($request->user(), $data['assigned_user_id']);
        } $followUp->update($data);

        return new FollowUpResource($followUp->refresh()->load('client'));
    }

    public function destroy(Request $request, FollowUp $followUp)
    {
        Gate::authorize('delete', $followUp);
        $followUp->delete();

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
