<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company, // Random company name
            'email' =>$this->faker->unique()->safeEmail(), // Optional and unique email
            'phone' => $this->faker->optional()->phoneNumber, // Optional phone number
            'address' => $this->faker->optional()->address, // Optional address
            'status' => 'active',
            'created_by' => 1, // Nullable, links to an existing user ID
            'created_at' => now(),
        ];
    }
}
