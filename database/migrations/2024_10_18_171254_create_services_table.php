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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('duration')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('outside_price', 10, 2)->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_target')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('branch_id')->constrained('branches')->default(1)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('service_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services', 'id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees', 'id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('commission_type', ['percentage', 'value'])->default('percentage');  // New column for commission type
            $table->unsignedDecimal('commission_value', 10, 2)->default(0);  // New column for commission value if it's a fixed value
            $table->boolean('is_immediate_commission')->default(false);  // Whether commission is immediate or not
            $table->timestamps();
        });

        Schema::create('service_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services', 'id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools', 'id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('tool_quantity')->default(1);  // Quantity of each tool for the service
            $table->timestamps();
        });

        Schema::create('service_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services', 'id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('product_quantity')->default(1);  // Quantity of each product for the service
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
