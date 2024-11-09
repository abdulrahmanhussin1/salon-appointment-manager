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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique(); // Changed code to string for potential alphanumeric support
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('supplier_price', 10, 2)->default(0);
            $table->decimal('customer_price', 10, 2)->default(0);
            $table->decimal('outside_price', 10, 2)->default(0);
            $table->unsignedInteger('initial_quantity')->default(0);
            $table->boolean('is_target')->default(false);
            $table->enum('type', ['operation', 'sales'])->default('operation');
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('products');
    }
};
