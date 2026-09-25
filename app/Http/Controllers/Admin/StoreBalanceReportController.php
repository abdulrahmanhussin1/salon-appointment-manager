<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransactionDetail;
use App\Models\Product;
use App\Traits\HasBranchFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class StoreBalanceReportController extends Controller
{
    use HasBranchFilter;

    public function index(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $inventoriesQuery = Inventory::where('status', 'active');
        if ($effectiveBranchId) {
            $inventoriesQuery->where('branch_id', $effectiveBranchId);
        }
        $inventories = $inventoriesQuery->get();

        return view('admin.pages.reports.stocke_balance_report', compact('inventories', 'branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function getData(Request $request)
    {
        $date = Carbon::now();
        $firstDayOfMonth = $date->startOfMonth();
        $lastDayOfMonth = $date->copy()->endOfMonth();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $query = Product::with(['supplierPrices'])
            ->select('products.*')
            ->when($effectiveBranchId, function ($query) use ($effectiveBranchId) {
                $query->where('products.branch_id', $effectiveBranchId);
            })
            ->when($request->inventory_id, function ($query) use ($request) {
                $query->whereHas('inventoryProducts', function ($q) use ($request) {
                    $q->where('inventory_id', $request->inventory_id);
                });
            });

        return DataTables::of($query)
            ->addColumn('unit_cost', function ($product) {
                return $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;
            })
            ->addColumn('beginning_qty', function ($product) use ($firstDayOfMonth, $effectiveBranchId, $request) {
                return InventoryProduct::where('product_id', $product->id)
                    ->when($effectiveBranchId, fn ($q) => $q->whereHas('inventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId)))
                    ->when($request->inventory_id, fn ($q) => $q->where('inventory_id', $request->inventory_id))
                    ->where('created_at', '<', $firstDayOfMonth)
                    ->sum('quantity');
            })
            ->addColumn('beginning_value', function ($product) use ($firstDayOfMonth, $effectiveBranchId, $request) {
                $qty = InventoryProduct::where('product_id', $product->id)
                    ->when($effectiveBranchId, fn ($q) => $q->whereHas('inventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId)))
                    ->when($request->inventory_id, fn ($q) => $q->where('inventory_id', $request->inventory_id))
                    ->where('created_at', '<', $firstDayOfMonth)
                    ->sum('quantity');
                $cost = $product->supplierPrices->sortBy('created_at')->first()->supplier_price ?? 0;

                return $qty * $cost;
            })
            ->addColumn('in_qty', function ($product) use ($firstDayOfMonth, $lastDayOfMonth, $effectiveBranchId, $request) {
                return InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($effectiveBranchId, $request) {
                    $query->where(function ($sub) {
                        $sub->whereIn('transaction_type', ['purchase', 'sales_return'])
                            ->orWhere(function ($adj) {
                                $adj->where('transaction_type', 'adjustment')
                                    ->where('adjustment_type', 'increase');
                            });
                    })
                        ->when($effectiveBranchId, fn ($tq) => $tq->where(function ($sub) use ($effectiveBranchId) {
                            $sub->whereHas('destinationInventory', fn ($dq) => $dq->where('branch_id', $effectiveBranchId))
                                ->orWhereHas('sourceInventory', fn ($sq) => $sq->where('branch_id', $effectiveBranchId));
                        }))
                        ->when($request->inventory_id, fn ($tq) => $tq->where('destination_inventory_id', $request->inventory_id));
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');
            })
            ->addColumn('in_value', function ($product) use ($firstDayOfMonth, $lastDayOfMonth, $effectiveBranchId, $request) {
                $qty = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($effectiveBranchId, $request) {
                    $query->where(function ($sub) {
                        $sub->whereIn('transaction_type', ['purchase', 'sales_return'])
                            ->orWhere(function ($adj) {
                                $adj->where('transaction_type', 'adjustment')
                                    ->where('adjustment_type', 'increase');
                            });
                    })
                        ->when($effectiveBranchId, fn ($tq) => $tq->where(function ($sub) use ($effectiveBranchId) {
                            $sub->whereHas('destinationInventory', fn ($dq) => $dq->where('branch_id', $effectiveBranchId))
                                ->orWhereHas('sourceInventory', fn ($sq) => $sq->where('branch_id', $effectiveBranchId));
                        }))
                        ->when($request->inventory_id, fn ($tq) => $tq->where('destination_inventory_id', $request->inventory_id));
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');

                $cost = $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;

                return $qty * $cost;
            })
            ->addColumn('out_qty', function ($product) use ($firstDayOfMonth, $lastDayOfMonth, $effectiveBranchId, $request) {
                return InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($effectiveBranchId, $request) {
                    $query->where(function ($sub) {
                        $sub->whereIn('transaction_type', ['sales', 'service_consumption'])
                            ->orWhere(function ($adj) {
                                $adj->where('transaction_type', 'adjustment')
                                    ->where('adjustment_type', 'decrease');
                            });
                    })
                        ->when($effectiveBranchId, fn ($tq) => $tq->where(function ($sub) use ($effectiveBranchId) {
                            $sub->whereHas('destinationInventory', fn ($dq) => $dq->where('branch_id', $effectiveBranchId))
                                ->orWhereHas('sourceInventory', fn ($sq) => $sq->where('branch_id', $effectiveBranchId));
                        }))
                        ->when($request->inventory_id, fn ($tq) => $tq->where('source_inventory_id', $request->inventory_id));
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');
            })
            ->addColumn('out_value', function ($product) use ($firstDayOfMonth, $lastDayOfMonth, $effectiveBranchId, $request) {
                $qty = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($effectiveBranchId, $request) {
                    $query->where(function ($sub) {
                        $sub->whereIn('transaction_type', ['sales', 'service_consumption'])
                            ->orWhere(function ($adj) {
                                $adj->where('transaction_type', 'adjustment')
                                    ->where('adjustment_type', 'decrease');
                            });
                    })
                        ->when($effectiveBranchId, fn ($tq) => $tq->where(function ($sub) use ($effectiveBranchId) {
                            $sub->whereHas('destinationInventory', fn ($dq) => $dq->where('branch_id', $effectiveBranchId))
                                ->orWhereHas('sourceInventory', fn ($sq) => $sq->where('branch_id', $effectiveBranchId));
                        }))
                        ->when($request->inventory_id, fn ($tq) => $tq->where('source_inventory_id', $request->inventory_id));
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');

                $cost = $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;

                return $qty * $cost;
            })
            ->addColumn('onhand_qty', function ($product) use ($effectiveBranchId, $request) {
                return InventoryProduct::where('product_id', $product->id)
                    ->when($effectiveBranchId, fn ($q) => $q->whereHas('inventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId)))
                    ->when($request->inventory_id, fn ($q) => $q->where('inventory_id', $request->inventory_id))
                    ->sum('quantity');
            })
            ->addColumn('onhand_value', function ($product) use ($effectiveBranchId, $request) {
                $qty = InventoryProduct::where('product_id', $product->id)
                    ->when($effectiveBranchId, fn ($q) => $q->whereHas('inventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId)))
                    ->when($request->inventory_id, fn ($q) => $q->where('inventory_id', $request->inventory_id))
                    ->sum('quantity');
                $cost = $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;

                return $qty * $cost;
            })
            ->make(true);
    }
}
