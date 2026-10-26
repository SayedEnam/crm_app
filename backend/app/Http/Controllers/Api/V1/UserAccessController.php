<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTeamMemberRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserAccessController extends Controller
{
    public function store(CreateTeamMemberRequest $request)
    {
        $data = $request->validated();
        if (! empty($data['assigned_manager_id'])) {
            abort_unless((int) $data['assigned_manager_id'] !== $request->user()->id && User::whereKey($data['assigned_manager_id'])->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('slug', 'manager'))->exists(), 422, 'Choose a different active manager.');
        }
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'assigned_manager_id' => $data['assigned_manager_id'] ?? null, 'is_active' => true, 'password' => Hash::make(Str::random(48))]);
            $user->roles()->attach(Role::where('slug', 'staff')->firstOrFail());

            return $user;
        });
        $status = Password::sendResetLink(['email' => $user->email]);

        return response()->json(['user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active], 'message' => $status === Password::RESET_LINK_SENT ? 'Team account created. A password setup link was sent.' : 'Team account created. Configure email delivery and send a reset link.'], 201);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $users = User::with('roles:id,name,slug')->orderBy('name')->paginate(min($request->integer('per_page', 30), 100));
        $users->getCollection()->transform(fn ($user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'is_active' => $user->is_active, 'assigned_manager_id' => $user->assigned_manager_id, 'roles' => $user->roles->map(fn ($role) => ['name' => $role->name, 'slug' => $role->slug])]);

        return response()->json($users);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:120'], 'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)], 'phone' => ['sometimes', 'nullable', 'string', 'max:40'], 'current_password' => ['required_with:password', 'current_password'], 'password' => ['sometimes', 'required', 'string', 'min:12', 'confirmed']]);
        unset($data['current_password']);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->fill($data)->save();

        return response()->json(['user' => $user->fresh()->load('roles:id,name,slug', 'roles.permissions:id,name,slug')]);
    }

    public function status(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        abort_if($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin'), 403);
        abort_if($request->user()->is($user) && ! $request->boolean('is_active'), 422, 'You cannot deactivate your own account.');
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update($data);
        if (! $user->is_active) {
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        }

        return response()->json(['id' => $user->id, 'is_active' => $user->is_active]);
    }

    public function roles(Request $request, User $user)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);
        $data = $request->validate(['roles' => ['required', 'array'], 'roles.*' => ['string', 'distinct', 'exists:roles,slug'], 'assigned_manager_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id']]);
        abort_if(in_array('super-admin', $data['roles'], true) && ! $request->user()->hasRole('super-admin'), 403);
        $user->roles()->sync(Role::whereIn('slug', $data['roles'])->pluck('id'));
        if (array_key_exists('assigned_manager_id', $data)) {
            $managerId = $data['assigned_manager_id'];
            if ($managerId !== null) {
                abort_unless((int) $managerId !== $user->id && User::whereKey($managerId)->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('slug', 'manager'))->exists(), 422, 'Select a different Manager account.');
            }
            $user->update(['assigned_manager_id' => $managerId]);
        }

        return response()->json(['id' => $user->id, 'roles' => $user->roles()->pluck('slug')]);
    }

    public function roleCatalog(Request $request)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        return response()->json(['data' => Role::with('permissions:id,name,slug')->orderBy('name')->get()]);
    }
}
