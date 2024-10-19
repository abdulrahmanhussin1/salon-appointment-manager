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
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('image')->nullable();
            $table->date('hiring_date')->nullable();
            $table->date('dob')->nullable();
            $table->string('finger_print_code')->nullable();
            $table->string('job_title')->nullable();
            $table->enum('gender',['male','female'])->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('employee_level_id')->nullable()->constrained('employee_levels','id')->cascadeOnUpdate()->cascadeOnDelete();
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
