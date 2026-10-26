<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FollowUp> */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return ['client_id' => Client::factory(), 'assigned_user_id' => User::factory(), 'due_at' => fake()->dateTimeBetween('now', '+30 days'), 'status' => fake()->randomElement(['pending', 'completed']), 'notes' => fake()->sentence()];
    }
}
