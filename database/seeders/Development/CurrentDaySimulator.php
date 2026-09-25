<?php

namespace Database\Seeders\Development;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrentDaySimulator extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();
        $today = $config->anchorDate->copy(); // 2026-09-25

        $todayStr = $today->toDateString();

        // 1. Live Appointments for Today
        $todayAppointments = [
            // Completed 1
            [
                'customer_id' => 12,
                'provider_id' => 5, // Sara Ahmed
                'service_id' => 7,  // Full Balayage
                'branch_id' => 1,
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'status' => AppointmentStatus::COMPLETED,
                'payment_method_id' => 2, // Card
                'tender' => 'card',
            ],
            // Completed 2
            [
                'customer_id' => 45,
                'provider_id' => 6, // Mohamed Nour
                'service_id' => 2,  // Men's Cut
                'branch_id' => 1,
                'start_time' => '10:30:00',
                'end_time' => '11:15:00',
                'status' => AppointmentStatus::COMPLETED,
                'payment_method_id' => 1, // Cash
                'tender' => 'cash',
            ],
            // Completed 3
            [
                'customer_id' => 88,
                'provider_id' => 9, // Dina Fathy
                'service_id' => 15, // Russian Gel Manicure
                'branch_id' => 1,
                'start_time' => '11:00:00',
                'end_time' => '12:15:00',
                'status' => AppointmentStatus::COMPLETED,
                'payment_method_id' => 2, // Card
                'tender' => 'card',
            ],
            // In Service
            [
                'customer_id' => 15,
                'provider_id' => 5, // Sara Ahmed
                'service_id' => 1,  // Women's Cut & Blowdry
                'branch_id' => 1,
                'start_time' => '12:30:00',
                'end_time' => '13:30:00',
                'status' => AppointmentStatus::IN_SERVICE,
                'payment_method_id' => null,
                'tender' => null,
            ],
            // Checked In
            [
                'customer_id' => 95,
                'provider_id' => 10, // Hoda Radwan
                'service_id' => 19,  // HydraFacial Deluxe
                'branch_id' => 1,
                'start_time' => '13:00:00',
                'end_time' => '14:15:00',
                'status' => AppointmentStatus::CHECKED_IN,
                'payment_method_id' => null,
                'tender' => null,
            ],
            // Confirmed 1 (Afternoon)
            [
                'customer_id' => 120,
                'provider_id' => 17, // Mostafa Kamel (Mall)
                'service_id' => 2,   // Men's Cut
                'branch_id' => 2,
                'start_time' => '14:30:00',
                'end_time' => '15:15:00',
                'status' => AppointmentStatus::CONFIRMED,
                'payment_method_id' => null,
                'tender' => null,
            ],
            // Confirmed 2 (Afternoon)
            [
                'customer_id' => 30, // VIP
                'provider_id' => 25, // Farida Fahmy (Westside)
                'service_id' => 21,  // Collagen Boost Facial
                'branch_id' => 3,
                'start_time' => '16:00:00',
                'end_time' => '17:30:00',
                'status' => AppointmentStatus::CONFIRMED,
                'payment_method_id' => null,
                'tender' => null,
            ],
            // Requested (Pending confirmation)
            [
                'customer_id' => 210,
                'provider_id' => 7,  // Layla Youssef
                'service_id' => 10,  // Keratin Smoothing
                'branch_id' => 1,
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'status' => AppointmentStatus::REQUESTED,
                'payment_method_id' => null,
                'tender' => null,
            ],
        ];

        foreach ($todayAppointments as $item) {
            $startDt = Carbon::parse("{$todayStr} {$item['start_time']}");
            $endDt = Carbon::parse("{$todayStr} {$item['end_time']}");

            $appt = Appointment::create([
                'customer_id' => $item['customer_id'],
                'provider_id' => $item['provider_id'],
                'service_id' => $item['service_id'],
                'status' => $item['status'],
                'start_date' => $startDt->format('Y-m-d H:i:s'),
                'end_date' => $endDt->format('Y-m-d H:i:s'),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $startDt->copy()->subDays(1),
                'updated_at' => $startDt,
            ]);

            // If completed, create sales invoice
            if ($item['status'] === AppointmentStatus::COMPLETED) {
                $srv = Service::find($item['service_id']);
                $price = (float) $srv->price;
                $commRate = 35.00;
                $commAmt = round(($price * $commRate) / 100, 2);

                $invoice = SalesInvoice::create([
                    'invoice_date' => $todayStr,
                    'total_amount' => $price,
                    'status' => 'active',
                    'refund_status' => 'none',
                    'total_refunded' => 0.00,
                    'invoice_discount' => 0.00,
                    'invoice_deposit' => 0.00,
                    'invoice_tax' => 0.00,
                    'net_total' => $price,
                    'paid_amount_cash' => ($item['tender'] === 'cash' ? $price : 0.00),
                    'payment_method_id' => $item['payment_method_id'],
                    'payment_method_value' => ($item['tender'] === 'card' ? $price : 0.00),
                    'balance_due' => 0.00,
                    'invoice_notes' => "Today's completed appointment #{$appt->id}",
                    'customer_id' => $item['customer_id'],
                    'appointment_id' => $appt->id,
                    'branch_id' => $item['branch_id'],
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $endDt,
                    'updated_at' => $endDt,
                ]);

                SalesInvoiceDetail::create([
                    'sales_invoice_id' => $invoice->id,
                    'service_id' => $srv->id,
                    'product_id' => null,
                    'provider_id' => $item['provider_id'],
                    'customer_price' => $price,
                    'quantity' => 1,
                    'discount' => 0.00,
                    'tax' => 0.00,
                    'subtotal' => $price,
                    'commission_type' => 'percentage',
                    'commission_rate' => $commRate,
                    'commission_amount' => $commAmt,
                    'is_immediate_commission' => false,
                    'refunded_quantity' => 0,
                    'refunded_amount' => 0.00,
                    'created_at' => $endDt,
                    'updated_at' => $endDt,
                ]);
            }
        }

        // 2. Retail Sale Today (walk-in retail purchase)
        $shampoo = Product::where('code', '100001')->first();
        if ($shampoo) {
            $walkinInv = SalesInvoice::create([
                'invoice_date' => $todayStr,
                'total_amount' => 38.00,
                'status' => 'active',
                'refund_status' => 'none',
                'total_refunded' => 0.00,
                'invoice_discount' => 0.00,
                'invoice_deposit' => 0.00,
                'invoice_tax' => 0.00,
                'net_total' => 38.00,
                'paid_amount_cash' => 38.00,
                'payment_method_id' => 1,
                'payment_method_value' => 0.00,
                'balance_due' => 0.00,
                'invoice_notes' => 'Today walk-in retail sale',
                'customer_id' => 14,
                'appointment_id' => null,
                'branch_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $today->copy()->setTime(11, 45),
                'updated_at' => $today->copy()->setTime(11, 45),
            ]);

            SalesInvoiceDetail::create([
                'sales_invoice_id' => $walkinInv->id,
                'service_id' => null,
                'product_id' => $shampoo->id,
                'provider_id' => 5,
                'customer_price' => 38.00,
                'quantity' => 1,
                'discount' => 0.00,
                'tax' => 0.00,
                'subtotal' => 38.00,
                'commission_type' => null,
                'commission_rate' => 0.00,
                'commission_amount' => 0.00,
                'is_immediate_commission' => false,
                'refunded_quantity' => 0,
                'refunded_amount' => 0.00,
                'created_at' => $today->copy()->setTime(11, 45),
                'updated_at' => $today->copy()->setTime(11, 45),
            ]);
        }

        // 3. Near-Future Bookings (Next 14 Days: 2026-09-26 to 2026-10-09)
        $futureServices = Service::all();
        $futureCustomers = Customer::all();
        $serviceEmployees = DB::table('service_employees')->get()->groupBy('service_id');

        for ($day = 1; $day <= 14; $day++) {
            $fDate = $today->copy()->addDays($day);
            $fDateStr = $fDate->toDateString();
            $slotsCount = mt_rand(4, 7);

            for ($s = 0; $s < $slotsCount; $s++) {
                $srv = $futureServices->random();
                $candidates = $serviceEmployees->get($srv->id);
                if (! $candidates || $candidates->isEmpty()) {
                    continue;
                }

                $provId = $candidates->random()->employee_id;
                $startHour = 11 + ($s * 1);
                if ($startHour > 19) {
                    continue;
                }

                $sTime = $fDate->copy()->setTime($startHour, (mt_rand(0, 3) * 15));
                $eTime = $sTime->copy()->addMinutes($srv->duration);

                // 85% Confirmed, 15% Requested
                $fStatus = (mt_rand(1, 100) <= 85) ? AppointmentStatus::CONFIRMED : AppointmentStatus::REQUESTED;

                Appointment::create([
                    'customer_id' => $futureCustomers->random()->id,
                    'provider_id' => $provId,
                    'service_id' => $srv->id,
                    'status' => $fStatus,
                    'start_date' => $sTime->format('Y-m-d H:i:s'),
                    'end_date' => $eTime->format('Y-m-d H:i:s'),
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $today->copy()->subHours(mt_rand(1, 48)),
                    'updated_at' => $today->copy()->subHours(mt_rand(1, 48)),
                ]);
            }
        }

        // 4. Set Specific Inventory Thresholds for Dashboard Alerts
        // Out of stock (quantity = 0):
        $outOfStockCodes = ['100004', '100010', '100015'];
        foreach ($outOfStockCodes as $code) {
            $prod = Product::where('code', $code)->first();
            if ($prod) {
                InventoryProduct::where('product_id', $prod->id)->update(['quantity' => 0]);
            }
        }

        // Low stock (quantity = 2 to 4 <= 5):
        $lowStockCodes = ['100001', '100007', '100011', '100018', '100050', '100052'];
        foreach ($lowStockCodes as $idx => $code) {
            $prod = Product::where('code', $code)->first();
            if ($prod) {
                $qty = 2 + ($idx % 3);
                InventoryProduct::where('product_id', $prod->id)->limit(1)->update(['quantity' => $qty]);
            }
        }
    }
}
