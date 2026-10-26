<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    public function status(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        abort_if($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin'), 403);
        abort_if($request->user()->is($user) && ! $request->boolean('is_active'), 422, 'You cannot deactivate your own account.');
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update($data);
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json(['id' => $user->id, 'is_active' => $user->is_active]);
    }

    public function roles(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('roles.manage'), 403);
        $data = $request->validate(['roles' => ['required', 'array'], 'roles.*' => ['string', 'exists:roles,slug']]);
        abort_if(in_array('super-admin', $data['roles'], true) && ! $request->user()->hasRole('super-admin'), 403);
        $user->roles()->sync(Role::whereIn('slug', $data['roles'])->pluck('id'));

        return response()->json(['id' => $user->id, 'roles' => $user->roles()->pluck('slug')]);
    }
}
