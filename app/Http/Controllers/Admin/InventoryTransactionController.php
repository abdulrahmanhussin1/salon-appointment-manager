<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class InventoryTransactionController extends Controller
{
    public function transferView()
    {
        $inventories = Inventory::where('status', 'active')->select('id', 'name')->get();
        $products = Product::with('supplierPrices')->where('status', 'active')->select('id', 'name')->get();

        return view('admin.pages.inventories.transactions.transfer', compact('inventories', 'products'));
    }

    public function transfer(Request $request)
    {
        $validatedData = $request->validate([
            'invoice_date' => 'required|date',
            'source_inventory' => 'required|exists:inventories,id',
            'destination_inventory' => 'required|exists:inventories,id|different:source_inventory',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
            'total_before_discount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'delivery_expense' => 'nullable|numeric|min:0',
            'other_expenses' => 'nullable|numeric|min:0',
            'added_value_tax' => 'nullable|numeric|min:0',
            'commercial_tax' => 'nullable|numeric|min:0',
            'net_total' => 'required|numeric|min:0',
        ]);

        // Check if the source inventory has enough stock for the transfer
        $sourceInventory = Inventory::findOrFail($validatedData['source_inventory']);
        $destinationInventory = Inventory::findOrFail($validatedData['destination_inventory']);

        // Aggregate quantities by product_id to ensure sufficient stock across line items
        $requestedQuantities = [];
        foreach ($validatedData['products'] as $product) {
            $requestedQuantities[$product['product_id']] = ($requestedQuantities[$product['product_id']] ?? 0) + $product['quantity'];
        }

        try {
            DB::transaction(function () use ($validatedData, $requestedQuantities) {
                // Step 1: Pessimistically lock and validate source stock inside the transaction
                foreach ($requestedQuantities as $productId => $totalRequestedQty) {
                    $sourceProduct = DB::table('inventory_products')
                        ->where('inventory_id', $validatedData['source_inventory'])
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (! $sourceProduct || $sourceProduct->quantity < $totalRequestedQty) {
                        throw new \DomainException(__('Not enough stock in the source inventory for the selected products.'));
                    }
                }

                // Step 2: Create the inventory transaction
                $transaction = InventoryTransaction::create([
                    'transaction_type' => 'transfer',
                    'source_inventory_id' => $validatedData['source_inventory'],
                    'destination_inventory_id' => $validatedData['destination_inventory'],
                    'total_before_discount' => $validatedData['total_before_discount'],
                    'discount' => $validatedData['discount'] ?? 0,
                    'delivery_expense' => $validatedData['delivery_expense'] ?? 0,
                    'other_expenses' => $validatedData['other_expenses'] ?? 0,
                    'added_value_tax' => $validatedData['added_value_tax'] ?? 0,
                    'commercial_tax' => $validatedData['commercial_tax'] ?? 0,
                    'net_total' => $validatedData['net_total'],
                ]);

                // Step 3: Update inventory levels and log product movements
                foreach ($validatedData['products'] as $product) {
                    // Deduct from source inventory
                    DB::table('inventory_products')
                        ->where('inventory_id', $validatedData['source_inventory'])
                        ->where('product_id', $product['product_id'])
                        ->decrement('quantity', $product['quantity']);

                    // Add to destination inventory - create record if it doesn't exist
                    $destinationProduct = DB::table('inventory_products')
                        ->where('inventory_id', $validatedData['destination_inventory'])
                        ->where('product_id', $product['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($destinationProduct) {
                        DB::table('inventory_products')
                            ->where('inventory_id', $validatedData['destination_inventory'])
                            ->where('product_id', $product['product_id'])
                            ->increment('quantity', $product['quantity']);
                    } else {
                        DB::table('inventory_products')->insert([
                            'inventory_id' => $validatedData['destination_inventory'],
                            'product_id' => $product['product_id'],
                            'quantity' => $product['quantity'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    InventoryTransactionDetail::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                    ]);
                }
            });

            Alert::success(__('Success'), __('Transfer transaction successfully stored.'));

            return redirect()->back();
        } catch (\DomainException $e) {
            Alert::error(__('Error'), $e->getMessage())->persistent(__('Close'));

            return redirect()->route('inventory_transactions.transferView');
        } catch (\Throwable $e) {
            Log::error('Transfer transaction failed: '.$e->getMessage(), ['exception' => $e]);
            Alert::error(__('Error'), __('Transfer transaction failed, please try again.'));

            return redirect()->back()->withInput();
        }
    }
}
