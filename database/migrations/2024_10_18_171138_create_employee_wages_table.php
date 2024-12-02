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
            $table->unsignedDecimal('basic_salary',10,2)->default(0);
            $table->unsignedDecimal('bonus_salary',10,2)->default(0);
            $table->unsignedDecimal('allowance1',10,2)->default(0);
            $table->unsignedDecimal('allowance2',10,2)->default(0);
            $table->unsignedDecimal('allowance3',10,2)->default(0);
            $table->unsignedDecimal('total_salary',10,2)->default(0);
            $table->unsignedDecimal('working_hours',10,2)->default(0);
            $table->time('start_working_time')->nullable();
            $table->unsignedDecimal('overtime_rate',10,2)->default(0);
            $table->unsignedDecimal('penalty_late_hour',10,2)->default(0);
            $table->unsignedDecimal('penalty_absence_day',10,2)->default(0);
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
