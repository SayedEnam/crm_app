<?php

namespace Database\Factories;

use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationRecord> */
class ApplicationRecordFactory extends Factory
{
    protected $model = ApplicationRecord::class;

    public function definition(): array
    {
        return ['client_id' => Client::factory(), 'assigned_user_id' => User::factory(), 'service_id' => Service::factory(), 'reference' => strtoupper(fake()->unique()->bothify('ZZ-########')), 'status' => fake()->randomElement(['active', 'in_progress', 'pending', 'completed']), 'started_at' => fake()->date(), 'notes' => fake()->optional()->sentence()];
    }
}
