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
        Schema::table('sales_invoice_details', function (Blueprint $table) {
            $table->enum('commission_type', ['percentage', 'value'])->nullable()->after('subtotal');
            $table->unsignedDecimal('commission_rate', 10, 2)->default(0)->after('commission_type');
            $table->unsignedDecimal('commission_amount', 10, 2)->default(0)->after('commission_rate');
            $table->boolean('is_immediate_commission')->default(false)->after('commission_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_details', function (Blueprint $table) {
            $table->dropColumn([
                'commission_type',
                'commission_rate',
                'commission_amount',
                'is_immediate_commission',
            ]);
        });
    }
};
