<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),  // Generates a random product name
            'code' => $this->faker->unique()->numberBetween(100000, 999999),  // Unique code between 100000 and 999999
            'description' => $this->faker->optional()->paragraph,  // Optional longer description
            'image' => $this->faker->optional()->imageUrl(640, 480, 'products'),  // Optional placeholder image
            'category_id' => $this->faker->optional()->numberBetween(1, 10),  // Assuming categories 1 to 10 exist
            'supplier_id' => $this->faker->optional()->numberBetween(1, 10),  // Assuming suppliers 1 to 10 exist
            'unit_id' => $this->faker->optional()->numberBetween(1, 3),  // Assuming units 1 to 5 exist
            // 'supplier_price' => $this->faker->randomFloat(2, 10, 200),  // Random price between 10 and 200
            // 'customer_price' => $this->faker->randomFloat(2, 20, 300),  // Higher random price between 20 and 300
            'status' => $this->faker->randomElement(['active', 'inactive']),  // Randomly active or inactive
            'branch_id' => rand(1,10), // Assuming Branch
            'created_by' => 1,  // Assuming users 1 to 5 exist
        ];
    }
}
