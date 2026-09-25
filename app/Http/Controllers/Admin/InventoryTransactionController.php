<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Product;
use App\Traits\HasBranchFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\DataTables;

class InventoryTransactionController extends Controller
{
    use HasBranchFilter;

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

    /**
     * Show form for manual stock adjustment (REQ-018).
     */
    public function adjustView(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $inventoriesQuery = Inventory::where('status', 'active');
        if ($effectiveBranchId) {
            $inventoriesQuery->where('branch_id', $effectiveBranchId);
        }
        $inventories = $inventoriesQuery->select('id', 'name', 'branch_id')->with('branch')->get();
        $products = Product::where('status', 'active')->select('id', 'name', 'code')->get();

        return view('admin.pages.inventories.transactions.adjust', compact('inventories', 'products', 'branches', 'canSelectAll', 'effectiveBranchId'));
    }

    /**
     * Retrieve current on-hand stock for a product in a given inventory.
     */
    public function checkStock(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|integer|exists:inventories,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $inventory = Inventory::findOrFail($request->inventory_id);

        if (! $this->canAccessAllBranches() && (int) $inventory->branch_id !== (int) auth()->user()->employee?->branch_id) {
            return response()->json(['error' => __('Unauthorized branch access')], 403);
        }

        $row = InventoryProduct::where('inventory_id', $request->inventory_id)
            ->where('product_id', $request->product_id)
            ->first();

        return response()->json([
            'quantity' => $row?->quantity ?? 0,
        ]);
    }

    /**
     * Store manual stock adjustment (REQ-018).
     */
    public function adjust(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'inventory_id' => 'required|exists:inventories,id',
            'adjustment_type' => 'required|in:increase,decrease',
            'adjustment_reason' => 'required|in:count_correction,damage,waste,theft,other',
            'notes' => 'required_if:adjustment_reason,other|nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $inventory = Inventory::findOrFail($validatedData['inventory_id']);

        // NFR-003: Server-side cashier branch isolation
        if (! $this->canAccessAllBranches() && (int) $inventory->branch_id !== (int) auth()->user()->employee?->branch_id) {
            abort(403, __('You are not authorized to adjust inventory for another branch.'));
        }

        // Aggregate quantities in case user specified the same product across multiple rows
        $requestedQuantities = [];
        foreach ($validatedData['products'] as $product) {
            $requestedQuantities[$product['product_id']] = ($requestedQuantities[$product['product_id']] ?? 0) + (int) $product['quantity'];
        }

        try {
            DB::transaction(function () use ($validatedData, $requestedQuantities, $inventory) {
                // If decreasing stock, validate that sufficient stock exists for all items
                if ($validatedData['adjustment_type'] === 'decrease') {
                    foreach ($requestedQuantities as $productId => $totalRequestedQty) {
                        $sourceProduct = DB::table('inventory_products')
                            ->where('inventory_id', $inventory->id)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();

                        $available = $sourceProduct?->quantity ?? 0;
                        if ($available < $totalRequestedQty) {
                            $prod = Product::find($productId);
                            $productName = $prod?->name ?? "Product #$productId";
                            throw new \DomainException(
                                __("Cannot deduct :quantity from product ':product'. Available stock in ':inventory' is only :available.", [
                                    'quantity' => $totalRequestedQty,
                                    'product' => $productName,
                                    'inventory' => $inventory->name,
                                    'available' => $available,
                                ])
                            );
                        }
                    }
                }

                // Create the inventory transaction
                $txDate = Carbon::parse($validatedData['date'])->setTimeFrom(now());
                $transaction = InventoryTransaction::create([
                    'transaction_type' => 'adjustment',
                    'adjustment_type' => $validatedData['adjustment_type'],
                    'adjustment_reason' => $validatedData['adjustment_reason'],
                    'source_inventory_id' => $validatedData['adjustment_type'] === 'decrease' ? $inventory->id : null,
                    'destination_inventory_id' => $validatedData['adjustment_type'] === 'increase' ? $inventory->id : null,
                    'notes' => $validatedData['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => $txDate,
                    'updated_at' => now(),
                ]);

                // Update inventory_products and create details
                foreach ($validatedData['products'] as $product) {
                    $productId = (int) $product['product_id'];
                    $qty = (int) $product['quantity'];

                    if ($validatedData['adjustment_type'] === 'decrease') {
                        DB::table('inventory_products')
                            ->where('inventory_id', $inventory->id)
                            ->where('product_id', $productId)
                            ->decrement('quantity', $qty);
                    } else {
                        $existing = DB::table('inventory_products')
                            ->where('inventory_id', $inventory->id)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();

                        if ($existing) {
                            DB::table('inventory_products')
                                ->where('inventory_id', $inventory->id)
                                ->where('product_id', $productId)
                                ->increment('quantity', $qty);
                        } else {
                            DB::table('inventory_products')->insert([
                                'inventory_id' => $inventory->id,
                                'product_id' => $productId,
                                'quantity' => $qty,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    InventoryTransactionDetail::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'created_at' => $txDate,
                        'updated_at' => now(),
                    ]);
                }
            });

            Alert::success(__('Success'), __('Stock adjustment recorded successfully.'));

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('Stock adjustment recorded successfully.')]);
            }

            return redirect()->route('inventory_transactions.history');
        } catch (\DomainException $e) {
            Alert::error(__('Stock Insufficient'), $e->getMessage())->persistent(__('Close'));

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            Log::error('Stock adjustment transaction failed: '.$e->getMessage(), ['exception' => $e]);
            Alert::error(__('Error'), __('Stock adjustment failed, please try again.'));

            if ($request->wantsJson()) {
                return response()->json(['error' => __('Stock adjustment failed, please try again.')], 500);
            }

            return redirect()->back()->withInput();
        }
    }

    /**
     * Show stock adjustment history / audit trail.
     */
    public function history(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $inventoriesQuery = Inventory::where('status', 'active');
        if ($effectiveBranchId) {
            $inventoriesQuery->where('branch_id', $effectiveBranchId);
        }
        $inventories = $inventoriesQuery->get();

        return view('admin.pages.inventories.transactions.history', compact('inventories', 'branches', 'canSelectAll', 'effectiveBranchId'));
    }

    /**
     * DataTables endpoint for stock adjustment history.
     */
    public function historyData(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $query = InventoryTransaction::adjustments()
            ->with(['sourceInventory.branch', 'destinationInventory.branch', 'transactionDetails.product', 'createdBy'])
            ->when($effectiveBranchId, function ($q) use ($effectiveBranchId) {
                $q->where(function ($sub) use ($effectiveBranchId) {
                    $sub->whereHas('sourceInventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId))
                        ->orWhereHas('destinationInventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId));
                });
            })
            ->when($request->inventory_id, function ($q, $invId) {
                $q->where(function ($sub) use ($invId) {
                    $sub->where('source_inventory_id', $invId)
                        ->orWhere('destination_inventory_id', $invId);
                });
            })
            ->when($request->adjustment_type, fn ($q, $type) => $q->where('adjustment_type', $type))
            ->when($request->adjustment_reason, fn ($q, $reason) => $q->where('adjustment_reason', $reason))
            ->when($request->from_date, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->to_date, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('id', 'desc');

        return DataTables::of($query)
            ->addColumn('inventory_name', function ($tx) {
                return $tx->inventory?->name ?? '—';
            })
            ->addColumn('branch_name', function ($tx) {
                return $tx->inventory?->branch?->name ?? '—';
            })
            ->addColumn('type_badge', function ($tx) {
                if ($tx->adjustment_type === 'increase') {
                    return '<span class="badge bg-success"><i class="bi bi-arrow-up-circle me-1"></i>'.__('Increase (+)').'</span>';
                }

                return '<span class="badge bg-danger"><i class="bi bi-arrow-down-circle me-1"></i>'.__('Decrease (-)').'</span>';
            })
            ->addColumn('reason_label', function ($tx) {
                return $tx->adjustment_reason_label;
            })
            ->addColumn('products_summary', function ($tx) {
                return $tx->transactionDetails->map(function ($d) {
                    return ($d->product?->name ?? 'Item #'.$d->product_id).' ('.(int) $d->quantity.')';
                })->implode(', ');
            })
            ->addColumn('user_name', function ($tx) {
                return $tx->createdBy?->name ?? '—';
            })
            ->addColumn('formatted_date', function ($tx) {
                return $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '—';
            })
            ->rawColumns(['type_badge'])
            ->make(true);
    }
}
