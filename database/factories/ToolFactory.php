<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tool>
 */
class ToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word, // Generates a single random word for category name
            'description' => $this->faker->optional()->sentence, // Optional, generates a sentence for description
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'branch_id' => rand(1,10), // Assuming Branch
            'created_by' => 1, // Nullable, links to an existing user ID
            'created_at' => now(),
        ];
    }
}
