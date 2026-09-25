<?php

namespace Database\Seeders\Development;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\SupplierPrice;
use App\Models\SupplierTransaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();
        $startDate = $config->startDate->copy();

        $products = Product::all();
        $inventories = Inventory::all()->keyBy('branch_id');

        // Price mapping matrix: [supplier_cost, retail_price]
        $priceMatrix = [
            '100001' => [22.00, 38.00], // Kerastase Shampoo
            '100002' => [26.00, 46.00], // Kerastase Oil
            '100003' => [20.00, 34.00], // Kerastase Mask
            '100004' => [32.00, 58.00], // Kerastase Serum
            '100005' => [18.00, 32.00], // Loreal Mask
            '100006' => [16.00, 28.00], // Loreal Metal Detox
            '100007' => [12.00, 24.00], // Loreal Spray
            '100008' => [14.00, 26.00], // Schwarzkopf Spray
            '100009' => [10.00, 20.00], // Schwarzkopf Dust It
            '100010' => [11.00, 22.00], // OPI Hand Cream
            '100011' => [8.00, 18.00],  // OPI Red
            '100012' => [8.00, 18.00],  // OPI Bubble Bath
            '100013' => [36.00, 64.00], // Dermalogica Microfoliant
            '100014' => [24.00, 44.00], // Dermalogica Cleansing Gel
            '100015' => [48.00, 89.00], // Dermalogica Vitamin C
            '100016' => [28.00, 48.00], // Moroccanoil Oil
            '100017' => [22.00, 39.00], // Moroccanoil Mask
            '100018' => [12.00, 22.00], // Reuzel Pomade
            '100019' => [11.00, 20.00], // Reuzel Balm
            '100020' => [9.00, 18.00],  // Proraso Shave Cream

            // Backbar Consumables
            '100050' => [6.50, 0.00],   // Color Tube (consumable)
            '100051' => [14.00, 0.00],  // Bleach Powder
            '100052' => [5.00, 0.00],   // Developer 20
            '100053' => [5.00, 0.00],   // Developer 30
            '100054' => [25.00, 0.00],  // 5L Clarifying Shampoo
            '100055' => [22.00, 0.00],  // 5L Acid Rinse
            '100056' => [18.00, 0.00],  // 1L Massage Oil
            '100057' => [12.00, 0.00],  // 1kg Moroccan Soap
            '100058' => [8.00, 0.00],   // 1L Acetone
            '100059' => [15.00, 0.00],  // Gel Duo
            '100060' => [10.00, 0.00],  // Towels pack
            '100061' => [7.50, 0.00],   // Gloves box
        ];

        // 1. Initial Restock Purchase Invoices at Start of Simulation (October 2025)
        $purchaseDates = [
            $startDate->copy()->addDays(2),
            $startDate->copy()->addMonths(3)->addDays(5),
            $startDate->copy()->addMonths(6)->addDays(10),
            $startDate->copy()->addMonths(9)->addDays(12),
        ];

        $invoiceCounter = 20250001;

        foreach ($purchaseDates as $batchIndex => $purchaseDate) {
            foreach ([1, 2, 3] as $branchId) {
                $inventory = $inventories[$branchId] ?? null;
                if (! $inventory) {
                    continue;
                }

                // Group products by supplier to create authentic per-supplier purchase invoices
                $productsBySupplier = $products->groupBy('supplier_id');

                foreach ($productsBySupplier as $supplierId => $supplierProducts) {
                    $invoiceTotal = 0;
                    $invoiceDetails = [];

                    // Adjust initial vs quarterly replenishment quantities
                    $qtyMultiplier = match ($batchIndex) {
                        0 => match ($branchId) { 1 => 40, 2 => 25, 3 => 15 }, // Initial bulk delivery
                        default => match ($branchId) { 1 => 15, 2 => 10, 3 => 6 }, // Replenishments
                    };

                    foreach ($supplierProducts as $product) {
                        [$costPrice, $custPrice] = $priceMatrix[$product->code] ?? [15.00, 30.00];
                        $qty = $qtyMultiplier + ($product->type === 'operation' ? 10 : 0);
                        $lineSubtotal = round($costPrice * $qty, 2);
                        $invoiceTotal += $lineSubtotal;

                        $invoiceDetails[] = [
                            'product' => $product,
                            'supplier_price' => $costPrice,
                            'customer_price' => $custPrice,
                            'quantity' => $qty,
                            'subtotal' => $lineSubtotal,
                        ];
                    }

                    if (empty($invoiceDetails)) {
                        continue;
                    }

                    $invoiceCounter++;
                    $purchaseInvoice = PurchaseInvoice::create([
                        'invoice_number' => $invoiceCounter,
                        'invoice_date' => $purchaseDate->toDateString(),
                        'total_amount' => $invoiceTotal,
                        'status' => 'active',
                        'invoice_discount' => 0,
                        'invoice_notes' => "Official restock shipment batch #{$batchIndex} from supplier.",
                        'supplier_id' => $supplierId,
                        'branch_id' => $branchId,
                        'created_by' => 1,
                        'created_at' => $purchaseDate,
                        'updated_at' => $purchaseDate,
                    ]);

                    // Create supplier transaction
                    SupplierTransaction::create([
                        'supplier_id' => $supplierId,
                        'reference_id' => $purchaseInvoice->id,
                        'reference_type' => 'purchase',
                        'amount' => $invoiceTotal,
                        'notes' => "Invoice #{$purchaseInvoice->invoice_number}",
                        'created_at' => $purchaseDate,
                        'updated_at' => $purchaseDate,
                    ]);

                    // Create inventory transaction (purchase)
                    $invTx = InventoryTransaction::create([
                        'transaction_type' => 'purchase',
                        'destination_inventory_id' => $inventory->id,
                        'total_before_discount' => $invoiceTotal,
                        'discount' => 0,
                        'net_total' => $invoiceTotal,
                        'created_at' => $purchaseDate,
                        'updated_at' => $purchaseDate,
                    ]);

                    foreach ($invoiceDetails as $det) {
                        PurchaseInvoiceDetail::create([
                            'purchase_invoice_id' => $purchaseInvoice->id,
                            'product_id' => $det['product']->id,
                            'supplier_price' => $det['supplier_price'],
                            'quantity' => $det['quantity'],
                            'subtotal' => $det['subtotal'],
                            'discount' => 0,
                            'created_at' => $purchaseDate,
                            'updated_at' => $purchaseDate,
                        ]);

                        InventoryTransactionDetail::create([
                            'inventory_transaction_id' => $invTx->id,
                            'product_id' => $det['product']->id,
                            'quantity' => $det['quantity'],
                            'created_at' => $purchaseDate,
                            'updated_at' => $purchaseDate,
                        ]);

                        // FIFO lot ledger in supplier_prices
                        SupplierPrice::create([
                            'supplier_id' => $supplierId,
                            'product_id' => $det['product']->id,
                            'purchase_invoice_id' => $purchaseInvoice->id,
                            'supplier_price' => $det['supplier_price'],
                            'customer_price' => $det['customer_price'],
                            'discount' => 0,
                            'quantity' => $det['quantity'],
                            'created_at' => $purchaseDate,
                            'updated_at' => $purchaseDate,
                        ]);

                        // Update or insert inventory_products current stock balance
                        $invProd = InventoryProduct::where('inventory_id', $inventory->id)
                            ->where('product_id', $det['product']->id)
                            ->first();

                        if ($invProd) {
                            $invProd->quantity += $det['quantity'];
                            $invProd->save();
                        } else {
                            InventoryProduct::create([
                                'inventory_id' => $inventory->id,
                                'product_id' => $det['product']->id,
                                'quantity' => $det['quantity'],
                                'created_at' => $purchaseDate,
                                'updated_at' => $purchaseDate,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
