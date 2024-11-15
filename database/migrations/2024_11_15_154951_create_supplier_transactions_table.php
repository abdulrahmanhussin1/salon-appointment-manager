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
        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('type', ['purchase','return' ,'payment', 'adjustment', 'initial_balance'])->default('purchase');
            $table->morphs('reference');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_transactions');
    }
};
