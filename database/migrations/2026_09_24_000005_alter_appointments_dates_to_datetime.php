<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointments MODIFY start_date DATETIME NOT NULL, MODIFY end_date DATETIME NOT NULL');
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['provider_id', 'start_date', 'end_date'], 'idx_appointments_provider_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('provider_id');
            $table->dropIndex('idx_appointments_provider_dates');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointments MODIFY start_date VARCHAR(255) NOT NULL, MODIFY end_date VARCHAR(255) NOT NULL');
        }
    }
};
