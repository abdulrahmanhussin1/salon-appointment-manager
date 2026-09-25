<?php

namespace Database\Seeders\Development;

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundDetail;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\SupplierPrice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentAndSalesSimulator extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();
        $startDate = $config->startDate->copy();
        $endDate = $config->anchorDate->copy()->subDay(); // Up to day before anchor

        // Preload metadata into memory for fast execution
        $customers = Customer::all()->keyBy('id');
        $services = Service::with(['products'])->get()->keyBy('id');
        $retailProducts = Product::where('type', 'sales')->get()->values();
        $consumableProducts = Product::where('type', 'operation')->get()->keyBy('id');

        // Service to providers mapping with commission rates
        $serviceEmployees = DB::table('service_employees')->get()->groupBy('service_id');

        // Service to consumable product requirements
        $serviceConsumables = DB::table('service_products')->get()->groupBy('service_id');

        // Inventories by branch
        $inventories = Inventory::all()->keyBy('branch_id');

        // Track provider active schedules per day: [date_str => [provider_id => [[startMin, endMin], ...]]]
        $providerSchedules = [];

        // Track in-memory stock balances to avoid DB roundtrips during simulation loop
        $stockBalances = []; // [branch_id => [product_id => quantity]]
        foreach (InventoryProduct::all() as $invProd) {
            $inv = Inventory::find($invProd->inventory_id);
            if ($inv) {
                $stockBalances[$inv->branch_id][$invProd->product_id] = (int) $invProd->quantity;
            }
        }

        $cancellationReasons = [
            'Client had an unexpected work emergency.',
            'Client reported feeling unwell.',
            'Family emergency arose.',
            'Delayed in heavy traffic, requested rescheduling.',
            'Client cancelled appointment due to travel conflict.',
            'Personal scheduling conflict.',
        ];

        $appointmentsToInsert = [];
        $salesInvoicesToInsert = [];
        $salesDetailsToInsert = [];
        $invTxToInsert = [];
        $invTxDetailsToInsert = [];
        $refundsToInsert = [];
        $refundDetailsToInsert = [];

        $appointmentId = 1;
        $invoiceId = 1;
        $detailId = 1;
        $invTxId = 1000;
        $refundId = 1;

        $currentDate = $startDate->copy();

        // Customer ID pools by cohort
        $vipIds = range(1, 90);
        $regularIds = range(91, 390);
        $occasionalIds = range(391, 750);
        $churnedIds = range(751, 970);
        $newIds = range(971, 1120);
        $edgeIds = range(1121, 1200);

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $monthNum = $currentDate->month;
            $dayOfWeek = $currentDate->dayOfWeek;

            $seasonMult = $config->getMonthlyMultiplier($monthNum);
            $dowMult = $config->getDayOfWeekMultiplier($dayOfWeek);

            // Daily appointments target: baseline ~12-16 scaled by seasonality and day-of-week
            $dailyCount = (int) round(14 * $seasonMult * $dowMult);
            $dailyCount = max(6, min(24, $dailyCount));

            $providerSchedules[$dateStr] = [];

            for ($a = 0; $a < $dailyCount; $a++) {
                // Select branch: 50% Downtown (1), 35% Mall (2), 15% Westside (3)
                $branchRand = mt_rand(1, 100);
                $branchId = ($branchRand <= 50) ? 1 : (($branchRand <= 85) ? 2 : 3);

                // Select customer based on cohort probability
                $cohortRand = mt_rand(1, 100);
                $customerId = match (true) {
                    $cohortRand <= 25 => $vipIds[mt_rand(0, count($vipIds) - 1)],
                    $cohortRand <= 65 => $regularIds[mt_rand(0, count($regularIds) - 1)],
                    $cohortRand <= 82 => $occasionalIds[mt_rand(0, count($occasionalIds) - 1)],
                    $cohortRand <= 90 => ($currentDate->diffInMonths($startDate) < 6) ? $churnedIds[mt_rand(0, count($churnedIds) - 1)] : $regularIds[mt_rand(0, count($regularIds) - 1)],
                    $cohortRand <= 95 => $edgeIds[mt_rand(0, count($edgeIds) - 1)],
                    default => ($currentDate->diffInMonths($startDate) >= 8) ? $newIds[mt_rand(0, count($newIds) - 1)] : $regularIds[mt_rand(0, count($regularIds) - 1)],
                };

                // Filter eligible services (branch or general)
                $eligibleServices = $services->filter(fn ($s) => $s->branch_id === $branchId || $s->branch_id === 1);
                $service = $eligibleServices->random();

                // Find eligible providers for this service
                $eligibleProviderRows = $serviceEmployees->get($service->id);
                if (! $eligibleProviderRows || $eligibleProviderRows->isEmpty()) {
                    continue;
                }

                // Pick a provider who belongs to this branch and was hired before this date
                $providerRow = null;
                $candidateRows = $eligibleProviderRows->shuffle();

                foreach ($candidateRows as $cand) {
                    $emp = Employee::find($cand->employee_id);
                    if (! $emp || $emp->branch_id !== $branchId) {
                        continue;
                    }
                    if ($emp->hiring_date > $dateStr) {
                        continue;
                    }
                    if ($emp->termination_date && $emp->termination_date < $dateStr) {
                        continue;
                    }
                    $providerRow = $cand;
                    break;
                }

                if (! $providerRow) {
                    continue;
                }

                $providerId = $providerRow->employee_id;
                $duration = $service->duration;

                // Pick a slot between 10:00 (minute 600) and 20:00 (minute 1200)
                $availableSlotFound = false;
                $slotStartMin = 600;
                $attempts = 0;

                while ($attempts < 6) {
                    $attempts++;
                    // Align to 15-minute intervals
                    $possibleStart = 600 + (mt_rand(0, (int) ((540 - $duration) / 15)) * 15);
                    $possibleEnd = $possibleStart + $duration;

                    // Check conflict in memory
                    $hasConflict = false;
                    if (isset($providerSchedules[$dateStr][$providerId])) {
                        foreach ($providerSchedules[$dateStr][$providerId] as [$sMin, $eMin]) {
                            if ($possibleStart < $eMin && $possibleEnd > $sMin) {
                                $hasConflict = true;
                                break;
                            }
                        }
                    }

                    if (! $hasConflict) {
                        $slotStartMin = $possibleStart;
                        $availableSlotFound = true;
                        break;
                    }
                }

                if (! $availableSlotFound) {
                    continue;
                }

                // Record scheduled slot
                $slotEndMin = $slotStartMin + $duration;
                $startHour = (int) floor($slotStartMin / 60);
                $startMinute = $slotStartMin % 60;
                $endHour = (int) floor($slotEndMin / 60);
                $endMinute = $slotEndMin % 60;

                $startDateTime = $currentDate->copy()->setTime($startHour, $startMinute, 0);
                $endDateTime = $currentDate->copy()->setTime($endHour, $endMinute, 0);

                // Determine lifecycle status
                // 72% Completed, 11% Cancelled, 5% No-show, 4% Rescheduled, 2% Rejected, 6% Confirmed
                $statusRand = mt_rand(1, 100);
                $status = match (true) {
                    $statusRand <= 72 => AppointmentStatus::COMPLETED,
                    $statusRand <= 83 => AppointmentStatus::CANCELLED,
                    $statusRand <= 88 => AppointmentStatus::NO_SHOW,
                    $statusRand <= 92 => AppointmentStatus::RESCHEDULED,
                    $statusRand <= 94 => AppointmentStatus::REJECTED,
                    default => AppointmentStatus::CONFIRMED,
                };

                // Reserve schedule slot in memory if active/completed
                if ($status === AppointmentStatus::COMPLETED || $status === AppointmentStatus::CONFIRMED) {
                    $providerSchedules[$dateStr][$providerId][] = [$slotStartMin, $slotEndMin];
                }

                $cancelledAt = null;
                $cancellationReason = null;
                if ($status === AppointmentStatus::CANCELLED) {
                    $cancelledAt = $startDateTime->copy()->subHours(mt_rand(2, 48));
                    $cancellationReason = $cancellationReasons[mt_rand(0, count($cancellationReasons) - 1)];
                }

                $currApptId = $appointmentId++;
                $appointmentsToInsert[] = [
                    'id' => $currApptId,
                    'customer_id' => $customerId,
                    'provider_id' => $providerId,
                    'service_id' => $service->id,
                    'status' => $status->value,
                    'cancelled_at' => $cancelledAt,
                    'cancellation_reason' => $cancellationReason,
                    'start_date' => $startDateTime->format('Y-m-d H:i:s'),
                    'end_date' => $endDateTime->format('Y-m-d H:i:s'),
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $startDateTime->copy()->subDays(mt_rand(1, 7)),
                    'updated_at' => $endDateTime,
                ];

                // If appointment is completed, generate SalesInvoice linked to appointment
                if ($status === AppointmentStatus::COMPLETED) {
                    $currInvoiceId = $invoiceId++;
                    $commRate = (float) $providerRow->commission_value; // e.g. 35.00
                    $servicePrice = (float) $service->price;

                    // Line item discount (VIP 10%, or promo 5%, or 0)
                    $discountPct = ($customerId <= 90) ? 10.00 : ((mt_rand(1, 10) === 1) ? 5.00 : 0.00);
                    $taxPct = 0.00; // Tax standard

                    $discountAmt = round(($servicePrice * $discountPct) / 100, 2);
                    $subtotal = round($servicePrice - $discountAmt, 2);
                    $commAmt = round(($subtotal * $commRate) / 100, 2);

                    $invoiceItemsGross = $servicePrice;
                    $invoiceDiscountTotal = $discountAmt;
                    $invoiceTaxTotal = 0.00;
                    $invoiceNetTotal = $subtotal;

                    // Add service detail line
                    $salesDetailsToInsert[] = [
                        'id' => $detailId++,
                        'sales_invoice_id' => $currInvoiceId,
                        'service_id' => $service->id,
                        'product_id' => null,
                        'provider_id' => $providerId,
                        'customer_price' => $servicePrice,
                        'quantity' => 1,
                        'discount' => $discountPct,
                        'tax' => $taxPct,
                        'subtotal' => $subtotal,
                        'commission_type' => 'percentage',
                        'commission_rate' => $commRate,
                        'commission_amount' => $commAmt,
                        'is_immediate_commission' => false,
                        'refunded_quantity' => 0,
                        'refunded_amount' => 0.00,
                        'notes' => null,
                        'created_at' => $endDateTime,
                        'updated_at' => $endDateTime,
                    ];

                    // 30% chance customer buys 1 retail product at checkout
                    if (mt_rand(1, 100) <= 30 && $retailProducts->isNotEmpty()) {
                        $retailProd = $retailProducts->random();
                        $retailPrice = match ($retailProd->code) {
                            '100001' => 38.00, '100002' => 46.00, '100003' => 34.00,
                            '100004' => 58.00, '100005' => 32.00, '100010' => 22.00,
                            '100013' => 64.00, '100016' => 48.00, '100018' => 22.00,
                            default => 25.00,
                        };

                        $prodSubtotal = $retailPrice;
                        $invoiceItemsGross += $retailPrice;
                        $invoiceNetTotal += $prodSubtotal;

                        $salesDetailsToInsert[] = [
                            'id' => $detailId++,
                            'sales_invoice_id' => $currInvoiceId,
                            'service_id' => null,
                            'product_id' => $retailProd->id,
                            'provider_id' => $providerId,
                            'customer_price' => $retailPrice,
                            'quantity' => 1,
                            'discount' => 0.00,
                            'tax' => 0.00,
                            'subtotal' => $prodSubtotal,
                            'commission_type' => null,
                            'commission_rate' => 0.00,
                            'commission_amount' => 0.00,
                            'is_immediate_commission' => false,
                            'refunded_quantity' => 0,
                            'refunded_amount' => 0.00,
                            'notes' => 'Retail aftercare product',
                            'created_at' => $endDateTime,
                            'updated_at' => $endDateTime,
                        ];

                        // Deduct stock in memory
                        if (isset($stockBalances[$branchId][$retailProd->id])) {
                            $stockBalances[$branchId][$retailProd->id] = max(0, $stockBalances[$branchId][$retailProd->id] - 1);
                        }
                    }

                    // Consume backbar products
                    $consRows = $serviceConsumables->get($service->id);
                    if ($consRows && $consRows->isNotEmpty()) {
                        foreach ($consRows as $cRow) {
                            if (isset($stockBalances[$branchId][$cRow->product_id])) {
                                $stockBalances[$branchId][$cRow->product_id] = max(0, $stockBalances[$branchId][$cRow->product_id] - (int) $cRow->product_quantity);
                            }
                        }
                    }

                    // Tender Selection
                    // 52% Cash, 40% Card, 5% Split, 3% Deposit
                    $tenderRand = mt_rand(1, 100);
                    $cashPaid = 0.00;
                    $cardPaid = 0.00;
                    $depositUsed = 0.00;
                    $balanceDue = 0.00;
                    $paymentMethodId = 1; // Cash

                    if ($tenderRand <= 52) {
                        $paymentMethodId = 1;
                        $cashPaid = $invoiceNetTotal;
                    } elseif ($tenderRand <= 92) {
                        $paymentMethodId = 2; // Card
                        $cardPaid = $invoiceNetTotal;
                    } elseif ($tenderRand <= 97) {
                        // Split Cash + Card
                        $paymentMethodId = 2;
                        $cashPaid = round($invoiceNetTotal * 0.5, 2);
                        $cardPaid = round($invoiceNetTotal - $cashPaid, 2);
                    } else {
                        // Deposit redemption if VIP
                        $depositUsed = min(200.00, $invoiceNetTotal);
                        $cashPaid = round($invoiceNetTotal - $depositUsed, 2);
                        $paymentMethodId = 1;
                    }

                    // Controlled Edge Case: 1.5% of invoices are voided
                    $isVoided = (mt_rand(1, 1000) <= 8 && $invoiceId > 20);
                    $status = $isVoided ? 'voided' : 'active';
                    $voidedAt = $isVoided ? $endDateTime->copy()->addHours(2) : null;
                    $voidedBy = $isVoided ? 1 : null;
                    $voidReason = $isVoided ? 'Customer requested appointment re-bill under separate corporate account.' : null;

                    $salesInvoicesToInsert[] = [
                        'id' => $currInvoiceId,
                        'invoice_date' => $dateStr,
                        'total_amount' => $invoiceItemsGross,
                        'status' => $status,
                        'refund_status' => 'none',
                        'total_refunded' => 0.00,
                        'invoice_discount' => $invoiceDiscountTotal,
                        'invoice_deposit' => $depositUsed,
                        'invoice_tax' => $invoiceTaxTotal,
                        'net_total' => $invoiceNetTotal,
                        'paid_amount_cash' => $cashPaid,
                        'payment_method_id' => $paymentMethodId,
                        'payment_method_value' => $cardPaid,
                        'balance_due' => $balanceDue,
                        'invoice_notes' => "Completed appointment #{$currApptId}",
                        'customer_id' => $customerId,
                        'appointment_id' => $currApptId,
                        'branch_id' => $branchId,
                        'created_by' => 1,
                        'updated_by' => 1,
                        'voided_by' => $voidedBy,
                        'voided_at' => $voidedAt,
                        'void_reason' => $voidReason,
                        'created_at' => $endDateTime,
                        'updated_at' => $endDateTime,
                    ];
                }
            }

            // Also add 1-2 Walk-in Retail Sales per day (pure product purchases)
            if ($retailProducts->isNotEmpty() && mt_rand(1, 10) <= 7) {
                $walkinProd = $retailProducts->random();
                $walkinPrice = 35.00;
                $walkinInvoiceId = $invoiceId++;
                $walkinBranchId = mt_rand(1, 2);
                $walkinCustomer = mt_rand(1, 300);
                $walkinTime = $currentDate->copy()->setTime(14, 30);

                $salesDetailsToInsert[] = [
                    'id' => $detailId++,
                    'sales_invoice_id' => $walkinInvoiceId,
                    'service_id' => null,
                    'product_id' => $walkinProd->id,
                    'provider_id' => ($walkinBranchId === 1 ? 5 : 17),
                    'customer_price' => $walkinPrice,
                    'quantity' => 1,
                    'discount' => 0.00,
                    'tax' => 0.00,
                    'subtotal' => $walkinPrice,
                    'commission_type' => null,
                    'commission_rate' => 0.00,
                    'commission_amount' => 0.00,
                    'is_immediate_commission' => false,
                    'refunded_quantity' => 0,
                    'refunded_amount' => 0.00,
                    'notes' => 'Walk-in client retail purchase',
                    'created_at' => $walkinTime,
                    'updated_at' => $walkinTime,
                ];

                $salesInvoicesToInsert[] = [
                    'id' => $walkinInvoiceId,
                    'invoice_date' => $dateStr,
                    'total_amount' => $walkinPrice,
                    'status' => 'active',
                    'refund_status' => 'none',
                    'total_refunded' => 0.00,
                    'invoice_discount' => 0.00,
                    'invoice_deposit' => 0.00,
                    'invoice_tax' => 0.00,
                    'net_total' => $walkinPrice,
                    'paid_amount_cash' => 0.00,
                    'payment_method_id' => 2, // Card
                    'payment_method_value' => $walkinPrice,
                    'balance_due' => 0.00,
                    'invoice_notes' => 'Direct walk-in retail sale',
                    'customer_id' => $walkinCustomer,
                    'appointment_id' => null,
                    'branch_id' => $walkinBranchId,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'voided_by' => null,
                    'voided_at' => null,
                    'void_reason' => null,
                    'created_at' => $walkinTime,
                    'updated_at' => $walkinTime,
                ];

                if (isset($stockBalances[$walkinBranchId][$walkinProd->id])) {
                    $stockBalances[$walkinBranchId][$walkinProd->id] = max(0, $stockBalances[$walkinBranchId][$walkinProd->id] - 1);
                }
            }

            // Flush chunked batch if buffer size reached to conserve memory and preserve FK constraints
            if (count($appointmentsToInsert) >= 200) {
                DB::table('appointments')->insert($appointmentsToInsert);
                $appointmentsToInsert = [];
                if (! empty($salesInvoicesToInsert)) {
                    DB::table('sales_invoices')->insert($salesInvoicesToInsert);
                    $salesInvoicesToInsert = [];
                }
                if (! empty($salesDetailsToInsert)) {
                    DB::table('sales_invoice_details')->insert($salesDetailsToInsert);
                    $salesDetailsToInsert = [];
                }
            }

            $currentDate->addDay();
        }

        // Flush remaining buffers in strict parent-to-child order
        if (! empty($appointmentsToInsert)) {
            DB::table('appointments')->insert($appointmentsToInsert);
            $appointmentsToInsert = [];
        }
        if (! empty($salesInvoicesToInsert)) {
            DB::table('sales_invoices')->insert($salesInvoicesToInsert);
            $salesInvoicesToInsert = [];
        }
        if (! empty($salesDetailsToInsert)) {
            DB::table('sales_invoice_details')->insert($salesDetailsToInsert);
            $salesDetailsToInsert = [];
        }

        // 2. Sync final stock balances back to inventory_products
        foreach ($stockBalances as $bId => $pBalances) {
            $invId = $inventories[$bId]->id ?? $bId;
            foreach ($pBalances as $pId => $qty) {
                DB::table('inventory_products')
                    ->where('inventory_id', $invId)
                    ->where('product_id', $pId)
                    ->update(['quantity' => max(0, $qty)]);
            }
        }

        // 3. Update customer last_service dates based on actual sales invoice history
        $latestDates = DB::table('sales_invoices')
            ->where('status', 'active')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MAX(invoice_date) as last_date')
            ->pluck('last_date', 'customer_id');

        foreach ($latestDates as $cId => $lDate) {
            DB::table('customers')->where('id', $cId)->update(['last_service' => $lDate]);
        }

        // 4. Generate ~35 Realistic Refunds (REQ-019) across historical sales invoices
        $refundableInvoices = SalesInvoice::where('status', 'active')
            ->where('net_total', '>=', 100)
            ->inRandomOrder()
            ->limit(35)
            ->get();

        $refundReasons = [
            'Customer experienced mild scalp sensitivity from treatment.',
            'Unopened retail product returned within 14-day exchange window.',
            'Client requested return of duplicate home care shampoo.',
            'Customer dissatisfied with service results, partial courtesy refund issued.',
        ];

        foreach ($refundableInvoices as $rInv) {
            $refundAmount = round(min(150.00, (float) $rInv->net_total * 0.4), 2);
            $rDate = Carbon::parse($rInv->invoice_date)->addDays(mt_rand(1, 5));
            if ($rDate->gt($endDate)) {
                $rDate = $endDate->copy();
            }

            $refundNumber = 'REF-'.$rDate->format('Ymd').'-'.str_pad((string) $refundId++, 4, '0', STR_PAD_LEFT);

            $refund = Refund::create([
                'refund_number' => $refundNumber,
                'sales_invoice_id' => $rInv->id,
                'customer_id' => $rInv->customer_id,
                'branch_id' => $rInv->branch_id,
                'refund_date' => $rDate->toDateString(),
                'refund_method' => (mt_rand(1, 10) <= 6) ? 'cash' : 'deposit',
                'total_refund_amount' => $refundAmount,
                'tax_refund_amount' => 0.00,
                'commission_reversed_amount' => round($refundAmount * 0.3, 2),
                'reason' => $refundReasons[mt_rand(0, count($refundReasons) - 1)],
                'notes' => 'Manager approved refund transaction.',
                'created_by' => 1,
                'created_at' => $rDate,
                'updated_at' => $rDate,
            ]);

            // Update sales invoice refund status
            $rInv->update([
                'refund_status' => 'partial',
                'total_refunded' => $refundAmount,
            ]);

            // Create refund detail line
            $detail = $rInv->salesInvoiceDetails()->first();
            if ($detail) {
                RefundDetail::create([
                    'refund_id' => $refund->id,
                    'sales_invoice_detail_id' => $detail->id,
                    'service_id' => $detail->service_id,
                    'product_id' => $detail->product_id,
                    'provider_id' => $detail->provider_id,
                    'quantity' => 1,
                    'unit_price' => $refundAmount,
                    'discount' => 0.00,
                    'tax' => 0.00,
                    'subtotal' => $refundAmount,
                    'commission_reversed' => round($refundAmount * 0.3, 2),
                    'inventory_restored' => ($detail->product_id !== null),
                    'inventory_id' => $inventories[$rInv->branch_id]->id ?? 1,
                    'notes' => 'Partial line refund',
                    'created_by' => 1,
                    'created_at' => $rDate,
                    'updated_at' => $rDate,
                ]);
            }
        }

        // 5. Generate ~25 Manual Stock Adjustments (REQ-018)
        $adjustmentReasons = ['count_correction', 'damage', 'waste', 'theft'];
        for ($adj = 1; $adj <= 25; $adj++) {
            $adjDate = $startDate->copy()->addDays(mt_rand(10, 340));
            $adjBranch = mt_rand(1, 3);
            $inv = $inventories[$adjBranch];
            $type = (mt_rand(1, 10) <= 7) ? 'decrease' : 'increase';
            $reason = $adjustmentReasons[mt_rand(0, count($adjustmentReasons) - 1)];

            $prod = $retailProducts->random();
            $adjQty = mt_rand(1, 3);

            InventoryTransaction::create([
                'transaction_type' => 'adjustment',
                'adjustment_type' => $type,
                'adjustment_reason' => $reason,
                'source_inventory_id' => ($type === 'decrease' ? $inv->id : null),
                'destination_inventory_id' => ($type === 'increase' ? $inv->id : null),
                'notes' => "Quarterly physical stock count adjustment ({$reason})",
                'created_by' => 1,
                'created_at' => $adjDate,
                'updated_at' => $adjDate,
            ]);
        }
    }
}
