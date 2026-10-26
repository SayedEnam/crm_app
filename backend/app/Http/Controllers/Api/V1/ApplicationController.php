<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ApplicationRecord::class);
        $q = ApplicationRecord::visibleTo($request->user())->with(['client:id,name', 'service:id,name,slug']);
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }

return ApplicationResource::collection($q->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(ApplicationRequest $request)
    {
        Gate::authorize('create', ApplicationRecord::class);
        $data = $request->validated();
        $client = Client::visibleTo($request->user())->findOrFail($data['client_id']);
        abort_unless(Service::whereKey($data['service_id'])->where('is_active', true)->exists(), 422, 'The selected service is inactive.');
        $data['assigned_user_id'] = $this->assignee($request->user(), $data['assigned_user_id'] ?? $client->assigned_user_id);
        $record = ApplicationRecord::create($data);

        return (new ApplicationResource($record->load(['client', 'service'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, ApplicationRecord $application)
    {
        Gate::authorize('view', $application);

        return new ApplicationResource($application->load(['client', 'service']));
    }

    public function update(ApplicationRequest $request, ApplicationRecord $application)
    {
        Gate::authorize('update', $application);
        $data = $request->validated();
        Client::visibleTo($request->user())->findOrFail($data['client_id']);
        abort_unless((int) $data['service_id'] === (int) $application->service_id || Service::whereKey($data['service_id'])->where('is_active', true)->exists(), 422, 'The selected service is inactive.');
        if (array_key_exists('assigned_user_id', $data)) {
            $data['assigned_user_id'] = $this->assignee($request->user(), $data['assigned_user_id']);
        } if (($data['status'] ?? null) === 'completed' && ! isset($data['completed_at'])) {
            $data['completed_at'] = today();
        } $application->update($data);

        return new ApplicationResource($application->refresh()->load(['client', 'service']));
    }

    public function destroy(Request $request, ApplicationRecord $application)
    {
        Gate::authorize('delete', $application);
        $application->delete();

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
            abort_unless($id === $user->id,403);
        }

return $id;
    }
}
