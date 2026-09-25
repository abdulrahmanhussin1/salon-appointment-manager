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
            DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('active', 'inactive', 'draft', 'voided') NOT NULL");
        }

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('voided_by')->nullable()->after('updated_by')->constrained('users', 'id')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['voided_by', 'voided_at', 'void_reason']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('active', 'inactive', 'draft') NOT NULL");
        }
    }
};
