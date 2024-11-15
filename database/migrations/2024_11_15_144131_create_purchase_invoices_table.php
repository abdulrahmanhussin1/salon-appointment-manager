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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_number')->unique();
            $table->date('invoice_date');
            $table->unsignedDecimal('total_amount');
            $table->enum('status', ['active', 'inactive']);
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
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
        Schema::dropIfExists('purchase_invoices');
    }
};
