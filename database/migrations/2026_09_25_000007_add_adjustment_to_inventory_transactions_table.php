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
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN transaction_type ENUM('purchase', 'sales', 'transfer', 'service_consumption', 'adjustment') NOT NULL");
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->enum('adjustment_type', ['increase', 'decrease'])->nullable()->after('transaction_type');
            $table->enum('adjustment_reason', ['count_correction', 'damage', 'waste', 'theft', 'other'])->nullable()->after('adjustment_type');
            $table->text('notes')->nullable()->after('adjustment_reason');
            $table->foreignId('created_by')->nullable()->after('net_total')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->cascadeOnUpdate()->nullOnDelete();

            $table->index('adjustment_type');
            $table->index('adjustment_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['adjustment_type']);
            $table->dropIndex(['adjustment_reason']);
            $table->dropColumn(['adjustment_type', 'adjustment_reason', 'notes', 'created_by', 'updated_by']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN transaction_type ENUM('purchase', 'sales', 'transfer', 'service_consumption') NOT NULL");
        }
    }
};
