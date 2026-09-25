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
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', [
                'requested',
                'confirmed',
                'rejected',
                'cancelled',
                'rescheduled',
                'checked_in',
                'in_service',
                'completed',
                'no_show',
                'expired',
            ])->default('requested')->after('service_id');

            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
