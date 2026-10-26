<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => fake()->phoneNumber(), 'company' => fake()->company(), 'country' => fake()->country(), 'status' => fake()->randomElement(['lead', 'active', 'inactive']), 'assigned_user_id' => User::factory()];
    }
}
