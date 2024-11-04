<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeWage>
 */
class EmployeeWageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' =>rand(1,10), // Assuming Employee factory exists
            'salary_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'commission']),
            'basic_salary' => $this->faker->randomFloat(2, 0, 10000), // Random basic salary up to 10,000
            'bonus_salary' => $this->faker->randomFloat(2, 0, 1000), // Random bonus salary
            'allowance1' => $this->faker->randomFloat(2, 0, 500),
            'allowance2' => $this->faker->randomFloat(2, 0, 500),
            'allowance3' => $this->faker->randomFloat(2, 0, 500),
            'total_salary' => 0, // Default; can be calculated later
            'working_hours' => $this->faker->randomFloat(2, 0, 24),
            'start_working_time' => $this->faker->time(),
            'overtime_rate' => $this->faker->randomFloat(2, 0, 100),
            'penalty_late_hour' => $this->faker->randomFloat(2, 0, 100),
            'penalty_absence_day' => $this->faker->randomFloat(2, 0, 100),
            'sales_target_settings' => $this->faker->randomElement(['no', 'total_sales', 'employee_daily_service']),
            'break_time' => $this->faker->time(),
            'break_duration_minutes' => $this->faker->numberBetween(0, 120), // Random break duration in minutes
        ];
    }
}
