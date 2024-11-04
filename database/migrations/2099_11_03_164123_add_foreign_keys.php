<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('manager_id')
                ->nullable()
                ->after('status')
                ->constrained('employees')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->after('status')
                ->constrained('branches', 'id')
                ->cascadeOnUpdate() 
                ->restrictOnDelete();

        });

        Schema::table('tools', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->after('status')
                ->constrained('branches', 'id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->after('status')

                ->constrained('branches', 'id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->after('status')

                ->constrained('branches', 'id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
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
