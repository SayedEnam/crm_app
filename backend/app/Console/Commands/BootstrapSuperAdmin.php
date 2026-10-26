<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapSuperAdmin extends Command
{
    protected $signature = 'crm:bootstrap-admin';

    protected $description = 'Create the first CRM super administrator interactively';

    public function handle(): int
    {
        if (User::whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))->exists()) {
            $this->components->error('A super administrator already exists.');

            return self::FAILURE;
        }
        $name = trim((string) $this->ask('Administrator name'));
        $email = strtolower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Password (12 characters minimum)');
        $confirm = (string) $this->secret('Confirm password');
        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            ['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:12', 'confirmed']],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }
        $role = Role::where('slug', 'super-admin')->firstOrFail();
        $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'is_active' => true]);
        $user->roles()->attach($role);
        $this->components->info('Super administrator created.');

        return self::SUCCESS;
    }
}
