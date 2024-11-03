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
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('manager_id')
            ->nullable()
            ->constrained('employees')
            ->nullOnDelete()
            ->cascadeOnUpdate();
        });

        Schema::table('units', function (Blueprint $table) {

            $table->foreignId('branch_id')
                ->constrained('branches', 'id')
                ->cascadeOnUpdate() // Update branch_id if the branch ID changes
                ->restrictOnDelete(); // Prevent deletion if there are related units

        });

        Schema::table('tools', function (Blueprint $table) {

            $table->foreignId('branch_id')
                ->constrained('branches', 'id')
                ->cascadeOnUpdate() // Update branch_id if the branch ID changes
                ->restrictOnDelete(); // Prevent deletion if there are related tools
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->constrained('branches', 'id')
                ->cascadeOnUpdate() // Update branch_id if the branch ID changes
                ->restrictOnDelete(); // Prevent deletion if there are related products
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->constrained('branches', 'id')
                ->cascadeOnUpdate() // Update branch_id if the branch ID changes
                ->restrictOnDelete(); // Prevent deletion if there are related employees
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
