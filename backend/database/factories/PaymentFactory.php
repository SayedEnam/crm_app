<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'paid']);

        return ['client_id' => Client::factory(), 'amount' => fake()->randomFloat(2, 100, 100000), 'currency' => 'BDT', 'status' => $status, 'method' => fake()->randomElement(['Cash', 'Bank', 'Card', 'Mobile banking']), 'paid_at' => $status === 'paid' ? fake()->date() : null];
    }
}
