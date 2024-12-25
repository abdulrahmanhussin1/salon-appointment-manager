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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('invoice_date');
            $table->unsignedDecimal('total_amount', 15, 2);
            $table->enum('status', ['active', 'inactive','draft']);
            $table->unsignedDecimal('invoice_discount', 10, 2)->default(0);
            $table->unsignedDecimal('invoice_deposit', 15, 2)->default(0);
            $table->unsignedDecimal('invoice_tax', 15, 2)->default(0);
            $table->unsignedDecimal('net_total', 15, 2)->default(0);
            $table->unsignedDecimal('paid_amount_cash', 15, 2)->default(0);
            $table->foreignId('payment_method_id')->constrained('payment_methods', 'id')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedDecimal('payment_method_value', 15, 2)->default(0);
            $table->unsignedDecimal('balance_due', 15, 2);
            $table->text('invoice_notes')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->default(1)->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users', 'id')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
