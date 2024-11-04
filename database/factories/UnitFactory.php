<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'symbol' => $this->faker->optional()->lexify('?'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'branch_id' =>rand(1,10), // Assuming Branch
            'created_by' => 1,
            'created_at' => now(),
        ];
    }
}
