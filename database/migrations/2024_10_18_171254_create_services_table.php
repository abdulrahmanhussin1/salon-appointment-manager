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
            $table->string('name');
            $table->text('description')->nullable();
            $table->time('time');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });


        Schema::create('service_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees','id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('service_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services','id')->cascadeOnUpdate()->cascadeOndelete();
            $table->foreignId('tool_id')->constrained('tools','id')->cascadeOnupdate()->cascadeOndelete();
            $table->timestamps();
        });
        Schema::create('service_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services','id')->cascadeOnUpdate()->cascadeOndelete();
            $table->foreignId('product_id')->constrained('products','id')->cascadeOnupdate()->cascadeOndelete();
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
