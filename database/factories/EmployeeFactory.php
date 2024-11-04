<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->phoneNumber,
            'national_id' => $this->faker->unique()->numerify('##########'), // Assuming 10 digits
            'hiring_date' => $this->faker->date(),
            'job_title' => $this->faker->word,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'status' => 'active', // Default value
            'employee_level_id' => rand(1,10), // Assuming EmployeeLevel factory exists
            'branch_id' => rand(1,10), // Assuming Branch
            'created_by' => 1, // Assuming a user with ID 1 exists, or replace with appropriate logic
            'updated_by' => null,
        ];
    }
}
