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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique();
            $table->string('national_id')->unique();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo')->nullable();
            $table->string('id_card')->nullable();
            $table->date('hiring_date');
            $table->date('dob')->nullable();
            $table->string('finger_print_code')->nullable()->unique();
            $table->string('job_title')->nullable();
            $table->enum('gender',['male','female'])->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('inactive_reason')->nullable();
            $table->date('termination_date')->nullable();
            $table->foreignId('employee_level_id')->constrained('employee_levels','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
