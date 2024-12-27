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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable()->unique();
            $table->text('address')->nullable();
            $table->enum('salutation', ['Mr', 'Mrs', 'Ms','Dr','Eng'])->default('Mr');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('dob')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_vip')->default(false);
            $table->enum('gender',['male', 'female'])->default('male');
            $table->date('last_service')->nullable();
            //$table->unsignedDecimal('deposit',10,2)->default(0);
            $table->enum('added_from', ['online', 'referral', 'walk_in', 'advertisement', 'direct'])->default('direct');
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
        Schema::dropIfExists('customers');
    }
};
