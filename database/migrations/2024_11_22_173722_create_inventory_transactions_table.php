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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['purchase', 'sales', 'transfer']);
            $table->foreignId('source_inventory_id')->nullable()->constrained('inventories', 'id')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('destination_inventory_id')->nullable()->constrained('inventories', 'id')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('total_before_discount', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('delivery_expense', 15, 2)->nullable();
            $table->decimal('other_expenses', 15, 2)->nullable();
            $table->decimal('added_value_tax', 5, 2)->nullable();
            $table->decimal('commercial_tax', 5, 2)->nullable();
            $table->decimal('net_total', 15, 2)->nullable();
            $table->timestamps();

            // Additional indexes for performance
            $table->index(['source_inventory_id', 'destination_inventory_id'], 'inventory_transfer_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
