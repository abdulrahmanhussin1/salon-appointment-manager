<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_wages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('salary_type', ['daily','weekly','monthly','commission'])->default('monthly');
            $table->unsignedDecimal('basic_salary')->default(0);
            $table->unsignedDecimal('bonus_salary')->default(0);
            $table->unsignedDecimal('allowance1')->default(0);
            $table->unsignedDecimal('allowance2')->default(0);
            $table->unsignedDecimal('allowance3')->default(0);
            $table->unsignedDecimal('total_salary')->default(0);
            $table->unsignedDecimal('working_hours')->default(0);
            $table->time('start_working_time')->nullable();
            $table->unsignedDecimal('overtime_rate')->default(0);
            $table->unsignedDecimal('penalty_late_hour')->default(0);
            $table->unsignedDecimal('penalty_absence_day')->default(0);
            $table->enum('sales_target_settings',['no','total_sales','employee_daily_service'])->default('no');
            $table->time('break_time')->nullable();
            $table->unsignedTinyInteger('break_duration_minutes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_wages');
    }
};
