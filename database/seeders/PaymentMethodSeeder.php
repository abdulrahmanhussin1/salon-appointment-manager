<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentMethod::create([
            'name' => 'credit card',
            'description' => 'Payment by Credit Card',
            'status' => 'active',
            'created_by' => 1
        ]);

        // PaymentMethod::create([
        //     'name' => 'cash',
        //     'description' => 'Payment by Cash',
        //     'status' => 'active',
        //     'created_by' => 1
        // ]);
    }
}
