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
            $table->unsignedBigInteger('code')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('supplier_price', 10, 2);
            $table->decimal('customer_price', 10, 2);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users','id')->cascadeOnUpdate()->cascadeOnDelete();
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
