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
        // 1. Update enum in MySQL for inventory_transactions
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN transaction_type ENUM('purchase', 'sales', 'transfer', 'service_consumption', 'adjustment', 'sales_return')");
        }

        // 2. Add refund status & tracking to sales_invoices
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->enum('refund_status', ['none', 'partial', 'full'])->default('none')->after('status');
            $table->unsignedDecimal('total_refunded', 15, 2)->default(0)->after('refund_status');
        });

        // 3. Add refunded quantity and amount tracking to sales_invoice_details
        Schema::table('sales_invoice_details', function (Blueprint $table) {
            $table->unsignedInteger('refunded_quantity')->default(0)->after('quantity');
            $table->decimal('refunded_amount', 15, 2)->default(0)->after('subtotal');
        });

        // 4. Create refunds table
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number', 50)->unique();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('refund_date');
            $table->enum('refund_method', ['cash', 'deposit', 'card', 'bank_transfer', 'other'])->default('cash');
            $table->decimal('total_refund_amount', 15, 2)->default(0);
            $table->decimal('tax_refund_amount', 15, 2)->default(0);
            $table->decimal('commission_reversed_amount', 15, 2)->default(0);
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'refund_date']);
            $table->index('sales_invoice_id');
        });

        // 5. Create refund_details table
        Schema::create('refund_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained('refunds')->cascadeOnDelete();
            $table->foreignId('sales_invoice_detail_id')->constrained('sales_invoice_details')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('employees')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('commission_reversed', 10, 2)->default(0);
            $table->boolean('inventory_restored')->default(false);
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->cascadeOnUpdate()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index('refund_id');
            $table->index('sales_invoice_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_details');
        Schema::dropIfExists('refunds');

        Schema::table('sales_invoice_details', function (Blueprint $table) {
            $table->dropColumn(['refunded_quantity', 'refunded_amount']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'total_refunded']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN transaction_type ENUM('purchase', 'sales', 'transfer', 'service_consumption', 'adjustment')");
        }
    }
};
