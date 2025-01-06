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
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->morphs('reference'); // Reference to models like SaleInvoice, Booking, etc.
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->enum('status', ['available', 'used'])->default('available');
            $table->foreignId('used_in_transaction_id')->nullable()->constrained('customer_transactions');
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
        Schema::dropIfExists('customer_transactions');
    }
};
