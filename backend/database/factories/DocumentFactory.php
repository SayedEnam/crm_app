<?php

namespace Database\Factories;

use App\Models\ApplicationRecord;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return ['application_id' => ApplicationRecord::factory(), 'uploaded_by' => User::factory(), 'name' => fake()->word().'.pdf', 'file_path' => null, 'mime_type' => 'application/pdf', 'status' => fake()->randomElement(['pending', 'received', 'approved', 'rejected']), 'notes' => fake()->optional()->sentence()];
    }
}
