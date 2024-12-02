<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company, // Generate a random company name
            'address' => $this->faker->address, // Generate a random address
            'phone' => $this->faker->phoneNumber, // Generate a random phone number
            'email' => $this->faker->unique()->safeEmail, // Generate a unique email address
            'status' => 'active', // Randomly select status
            'created_by' => 1, // Assuming you have a User factory to create a user for the created_by field
        ];
    }
}
