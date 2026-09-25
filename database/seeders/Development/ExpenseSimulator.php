<?php

namespace Database\Seeders\Development;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseSimulator extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();
        $startDate = $config->startDate->copy();
        $endDate = $config->anchorDate->copy(); // Up through current date

        $expensesToInsert = [];
        $invoiceSeq = 5001;

        $currentMonth = $startDate->copy()->startOfMonth();

        while ($currentMonth->lte($endDate)) {
            $monthNum = $currentMonth->month;
            $seasonMultiplier = $config->getMonthlyMultiplier($monthNum);

            foreach ([1, 2, 3] as $branchId) {
                $branchRent = match ($branchId) {
                    1 => 4500.00,
                    2 => 6000.00,
                    3 => 3000.00,
                };

                $rentDate = $currentMonth->copy()->addDays(1)->setTime(10, 0);
                if ($rentDate->lte($endDate)) {
                    $invoiceSeq++;
                    $expensesToInsert[] = [
                        'expense_type_id' => 1, // Rent
                        'description' => "Monthly salon space lease for Branch #{$branchId}",
                        'amount' => $branchRent,
                        'paid_amount' => $branchRent,
                        'balance' => 0.00,
                        'paid_at' => $rentDate->format('Y-m-d H:i:s'),
                        'invoice_number' => "RENT-{$currentMonth->format('Ym')}-B{$branchId}",
                        'payment_method_id' => 3, // Bank transfer
                        'status' => 'active',
                        'branch_id' => $branchId,
                        'created_by' => 1,
                        'created_at' => $rentDate,
                        'updated_at' => $rentDate,
                    ];
                }

                // Software Subscription (5th of month)
                $softDate = $currentMonth->copy()->addDays(4)->setTime(11, 30);
                if ($softDate->lte($endDate)) {
                    $invoiceSeq++;
                    $expensesToInsert[] = [
                        'expense_type_id' => 7, // Software & POS
                        'description' => 'Salon Appointment & Cloud POS SaaS Subscription',
                        'amount' => 150.00,
                        'paid_amount' => 150.00,
                        'balance' => 0.00,
                        'paid_at' => $softDate->format('Y-m-d H:i:s'),
                        'invoice_number' => "INV-SUB-{$currentMonth->format('Ym')}-B{$branchId}",
                        'payment_method_id' => 2, // Credit card
                        'status' => 'active',
                        'branch_id' => $branchId,
                        'created_by' => 1,
                        'created_at' => $softDate,
                        'updated_at' => $softDate,
                    ];
                }

                // Utilities (10th of month)
                $utilDate = $currentMonth->copy()->addDays(9)->setTime(14, 0);
                if ($utilDate->lte($endDate)) {
                    $baseUtil = match ($branchId) {
                        1 => 750.00,
                        2 => 950.00,
                        3 => 450.00,
                    };
                    $utilAmount = round($baseUtil * $seasonMultiplier + mt_rand(-30, 50), 2);
                    $invoiceSeq++;
                    $expensesToInsert[] = [
                        'expense_type_id' => 2, // Utilities
                        'description' => 'Commercial Electricity, HVAC & Water Bill',
                        'amount' => $utilAmount,
                        'paid_amount' => $utilAmount,
                        'balance' => 0.00,
                        'paid_at' => $utilDate->format('Y-m-d H:i:s'),
                        'invoice_number' => "UTIL-{$currentMonth->format('Ym')}-B{$branchId}",
                        'payment_method_id' => 2, // Credit card
                        'status' => 'active',
                        'branch_id' => $branchId,
                        'created_by' => 1,
                        'created_at' => $utilDate,
                        'updated_at' => $utilDate,
                    ];
                }

                // Bi-weekly Cleaning & Sanitation
                for ($week = 1; $week <= 2; $week++) {
                    $cleanDate = $currentMonth->copy()->addDays($week * 13)->setTime(19, 0);
                    if ($cleanDate->lte($endDate)) {
                        $cleanAmount = round(180.00 + ($branchId * 30) + mt_rand(-15, 25), 2);
                        $invoiceSeq++;
                        $expensesToInsert[] = [
                            'expense_type_id' => 8, // Cleaning
                            'description' => 'Professional night cleaning and deep sterilization',
                            'amount' => $cleanAmount,
                            'paid_amount' => $cleanAmount,
                            'balance' => 0.00,
                            'paid_at' => $cleanDate->format('Y-m-d H:i:s'),
                            'invoice_number' => "CLN-{$cleanDate->format('Ymd')}-B{$branchId}",
                            'payment_method_id' => 1, // Cash
                            'status' => 'active',
                            'branch_id' => $branchId,
                            'created_by' => 1,
                            'created_at' => $cleanDate,
                            'updated_at' => $cleanDate,
                        ];
                    }
                }

                // Supplies & Hospitality (Weekly)
                for ($w = 1; $w <= 3; $w++) {
                    $suppDate = $currentMonth->copy()->addDays($w * 7)->setTime(16, 0);
                    if ($suppDate->lte($endDate)) {
                        $suppAmount = round(120.00 + mt_rand(10, 80), 2);
                        $invoiceSeq++;
                        $expensesToInsert[] = [
                            'expense_type_id' => 3, // Supplies
                            'description' => 'Disposable towels, salon refreshments, espresso beans & guest water',
                            'amount' => $suppAmount,
                            'paid_amount' => $suppAmount,
                            'balance' => 0.00,
                            'paid_at' => $suppDate->format('Y-m-d H:i:s'),
                            'invoice_number' => "SUP-{$suppDate->format('Ymd')}-B{$branchId}",
                            'payment_method_id' => 1, // Cash
                            'status' => 'active',
                            'branch_id' => $branchId,
                            'created_by' => 1,
                            'created_at' => $suppDate,
                            'updated_at' => $suppDate,
                        ];
                    }
                }

                // Periodic Marketing / Social Media Campaign
                if ($monthNum % 2 === 0) {
                    $mktDate = $currentMonth->copy()->addDays(18)->setTime(13, 0);
                    if ($mktDate->lte($endDate)) {
                        $mktAmount = round(450.00 + ($branchId * 100) + mt_rand(0, 150), 2);
                        $invoiceSeq++;
                        $expensesToInsert[] = [
                            'expense_type_id' => 5, // Marketing
                            'description' => 'Instagram & TikTok local sponsored promotion and influencer visit',
                            'amount' => $mktAmount,
                            'paid_amount' => $mktAmount,
                            'balance' => 0.00,
                            'paid_at' => $mktDate->format('Y-m-d H:i:s'),
                            'invoice_number' => "MKT-{$mktDate->format('Ymd')}-B{$branchId}",
                            'payment_method_id' => 2, // Credit card
                            'status' => 'active',
                            'branch_id' => $branchId,
                            'created_by' => 1,
                            'created_at' => $mktDate,
                            'updated_at' => $mktDate,
                        ];
                    }
                }

                // Periodic Equipment Maintenance (every 3 months)
                if ($monthNum % 3 === 0) {
                    $maintDate = $currentMonth->copy()->addDays(22)->setTime(15, 0);
                    if ($maintDate->lte($endDate)) {
                        $maintAmount = round(320.00 + mt_rand(20, 150), 2);
                        $invoiceSeq++;
                        $expensesToInsert[] = [
                            'expense_type_id' => 4, // Maintenance
                            'description' => 'HVAC filter service, salon hydraulic chair tuning and steamer descaling',
                            'amount' => $maintAmount,
                            'paid_amount' => $maintAmount,
                            'balance' => 0.00,
                            'paid_at' => $maintDate->format('Y-m-d H:i:s'),
                            'invoice_number' => "MNT-{$maintDate->format('Ymd')}-B{$branchId}",
                            'payment_method_id' => 1, // Cash
                            'status' => 'active',
                            'branch_id' => $branchId,
                            'created_by' => 1,
                            'created_at' => $maintDate,
                            'updated_at' => $maintDate,
                        ];
                    }
                }
            }

            $currentMonth->addMonth();
        }

        // Fast chunked insert for expenses
        foreach (array_chunk($expensesToInsert, 200) as $chunk) {
            DB::table('expenses')->insert($chunk);
        }
    }
}
