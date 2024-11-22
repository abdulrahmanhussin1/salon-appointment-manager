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
            $table->foreignId('product_id')->constrained('products', 'id')->cascadeOnUpdate()->restrictOnDelete();
            $table->morphs('reference'); // e.g., supplier, customer, inventory
            $table->enum('transaction_type', ['in', 'out', 'transfer']);
            $table->foreignId('source_inventory_id')->nullable()->constrained('inventories', 'id')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('destination_inventory_id')->nullable()->constrained('inventories', 'id')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedBigInteger('quantity'); // Updated for large-scale systems
            $table->timestamps();

            // Additional indexes for performance
            $table->index(['reference_id', 'reference_type'], 'inventory_ref_index');
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
