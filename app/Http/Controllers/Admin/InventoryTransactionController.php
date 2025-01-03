<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use RealRashid\SweetAlert\Facades\Alert;

class InventoryTransactionController extends Controller
{

    public function transferView()
    {
        $inventories = Inventory::where('status', 'active')->select('id', 'name')->get();
        $products = Product::with('supplierPrices')->where('status', 'active')->select('id', 'name')->get();
        return view('admin.pages.inventories.transactions.transfer', compact('inventories', 'products'));
    }

    public function Transfer(Request $request)
    {
        $validatedData = $request->validate([
            'invoice_date' => 'required|date',
            'source_inventory' => 'required|exists:inventories,id',
            'destination_inventory' => 'required|exists:inventories,id|different:source_inventory',
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
        $sourceInventory = Inventory::find($validatedData['source_inventory']);
        $destinationInventory = Inventory::find($validatedData['destination_inventory']);

        foreach ($validatedData['products'] as $product) {
            $sourceProduct = $sourceInventory->inventoryProducts()->where('product_id', $product['product_id'])->first();

           // dd($sourceProduct);
            if (empty($sourceProduct->quantity) || $sourceProduct->quantity < $product['quantity']) {
                Alert::error('Error', 'Not enough stock in the source inventory for the selected products.')->persistent('Close');
                return redirect()->route('inventory_transactions.transferView');
            }
        }

        try {
            DB::transaction(function () use ($validatedData) {
                // Step 1: Create the inventory transaction
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

                // Step 2: Update inventory levels and log product movements
                foreach ($validatedData['products'] as $product) {
                    // Deduct from source inventory
                    DB::table('inventory_products')
                        ->where('inventory_id', $validatedData['source_inventory'])
                        ->where('product_id', $product['product_id'])
                        ->decrement('quantity', $product['quantity']);

                    // Add to destination inventory
                    DB::table('inventory_products')
                        ->where('inventory_id', $validatedData['destination_inventory'])
                        ->where('product_id', $product['product_id'])
                        ->increment('quantity', $product['quantity']);


                    InventoryTransactionDetail::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                    ]);

                    // // Optionally, log each product transfer for auditing
                    // DB::table('inventory_product_movements')->insert([
                    //     'transaction_id' => $transaction->id,
                    //     'product_id' => $product['product_id'],
                    //     'quantity' => $product['quantity'],
                    //     'unit_price' => $product['unit_price'],
                    //     'source_inventory_id' => $validatedData['source_inventory'],
                    //     'destination_inventory_id' => $validatedData['destination_inventory'],
                    //     'created_at' => now(),
                    //     'updated_at' => now(),
                    // ]);
                }
            });

            DB::commit();
            Alert::success(__(key: 'Success'), __('Transfer transaction successfully stored.'));
            return redirect()->back();

           // return response()->json(['message' => 'Transfer transaction successfully stored.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::success(__(key: 'Error'), __('Try Again'));
            return redirect()->back();

           // return response()->json(['message' => 'Transfer transaction failed'], 500);
        }
    }
}
