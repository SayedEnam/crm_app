<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('services.view') || $request->user()->hasPermission('applications.view'), 403);

        return Service::query()->when(! $request->user()->hasPermission('services.manage'), fn ($q) => $q->where('is_active', true))->orderBy('name')->paginate(50);
    }

    public function show(Request $request, Service $service)
    {
        abort_unless($request->user()->hasPermission('services.view') || $request->user()->hasPermission('applications.view'), 403);
        abort_if(! $service->is_active && ! $request->user()->hasPermission('services.manage'), 404);

        return $service;
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-services');
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'slug' => ['required', 'alpha_dash', 'max:150', 'unique:services,slug'], 'description' => ['nullable', 'string', 'max:3000'], 'is_active' => ['sometimes', 'boolean']]);

        return response()->json(Service::create($data), 201);
    }

    public function update(Request $request, Service $service)
    {
        Gate::authorize('manage-services');
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:150'], 'slug' => ['sometimes', 'required', 'alpha_dash', 'max:150', 'unique:services,slug,'.$service->id], 'description' => ['nullable', 'string', 'max:3000'], 'is_active' => ['sometimes', 'boolean']]);
        $service->update($data);

        return $service->refresh();
    }

    public function destroy(Request $request, Service $service)
    {
        Gate::authorize('manage-services');
        abort_if($service->applications()->exists(), 409, 'A service with applications cannot be deleted.');
        $service->delete();

        return response()->noContent();
    }
}
