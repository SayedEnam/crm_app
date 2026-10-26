<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AssigneeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('clients.manage') || $user->hasPermission('applications.manage') || $user->hasPermission('followups.manage'), 403);
        $query = User::query()->where('is_active', true);
        if (! $user->hasRole('super-admin') && ! $user->hasRole('admin')) {
            if ($user->hasRole('manager')) {
                $query->where(fn ($q) => $q->whereKey($user->id)->orWhere('assigned_manager_id', $user->id));
            } else {
                $query->whereKey($user->id);
            }
        }

        return response()->json(['data' => $query->orderBy('name')->get(['id', 'name'])]);
    }
}
