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

        Schema::table('supplier_prices', function (BluePrint $table) {
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->after('product_id')->cascadeOnUpdate()->restrictOnDelete();
        });

        schema::table('users', function (BluePrint $table) {
            $table->foreignId('employee_id')->nullable()->after('email')->constrained('employees')->cascadeOnUpdate()->restrictOnDelete();
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
