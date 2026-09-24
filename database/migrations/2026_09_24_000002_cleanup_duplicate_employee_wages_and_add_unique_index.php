<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up duplicate records if any exist
        $duplicateEmployeeIds = DB::table('employee_wages')
            ->select('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('employee_id');

        foreach ($duplicateEmployeeIds as $employeeId) {
            // Pick the best record (one with non-zero total_salary or highest id)
            $bestWage = DB::table('employee_wages')
                ->where('employee_id', $employeeId)
                ->orderByDesc('total_salary')
                ->orderByDesc('id')
                ->first();

            if ($bestWage) {
                DB::table('employee_wages')
                    ->where('employee_id', $employeeId)
                    ->where('id', '!=', $bestWage->id)
                    ->delete();
            }
        }

        // 2. Add unique index to prevent future duplicates at the database level
        Schema::table('employee_wages', function (Blueprint $table) {
            $table->unique('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_wages', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
        });
    }
};
