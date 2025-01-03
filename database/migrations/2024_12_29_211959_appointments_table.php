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
        Schema::create(
            'appointments',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('start_date');
                $table->string('end_date');

                 $table->unsignedBigInteger('customer_id');
                $table->foreign('customer_id')->references('id')->on('customers');

                $table->unsignedBigInteger('provider_id');
                $table->foreign('provider_id')->references('id')->on('employees');

                $table->unsignedBigInteger('service_id');
                $table->foreign('service_id')->references('id')->on('services');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');

                $table->unsignedBigInteger('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users');

                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
