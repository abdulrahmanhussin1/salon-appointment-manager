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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_type_id')->constrained('expense_types','id')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->default(now());

            $table->string('invoice_number')->nullable();
            $table->unsignedDecimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);

            $table->foreignId('payment_method_id')->constrained('payment_methods','id')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('branch_id')->constrained('branches')->default(1)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users','id')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users','id')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
