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

        $beginningQtyMap = InventoryProduct::where('created_at', '<', $firstDayOfMonth)
            ->when($effectiveBranchId, fn ($q) => $q->whereHas('inventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId)))
            ->when($request->inventory_id, fn ($q) => $q->where('inventory_id', $request->inventory_id))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->pluck('total_qty', 'product_id');

        $inQtyMap = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($effectiveBranchId, $request) {
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
            ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->pluck('total_qty', 'product_id');

        $outQtyMap = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($effectiveBranchId, $request) {
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
            ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->pluck('total_qty', 'product_id');

        $onHandQtyMap = InventoryProduct::when($effectiveBranchId, fn ($q) => $q->whereHas('inventory', fn ($iq) => $iq->where('branch_id', $effectiveBranchId)))
            ->when($request->inventory_id, fn ($q) => $q->where('inventory_id', $request->inventory_id))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->pluck('total_qty', 'product_id');

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
            ->addColumn('beginning_qty', function ($product) use ($beginningQtyMap) {
                return (float) ($beginningQtyMap[$product->id] ?? 0);
            })
            ->addColumn('beginning_value', function ($product) use ($beginningQtyMap) {
                $qty = (float) ($beginningQtyMap[$product->id] ?? 0);
                $cost = (float) ($product->supplierPrices->sortBy('created_at')->first()->supplier_price ?? 0);

                return $qty * $cost;
            })
            ->addColumn('in_qty', function ($product) use ($inQtyMap) {
                return (float) ($inQtyMap[$product->id] ?? 0);
            })
            ->addColumn('in_value', function ($product) use ($inQtyMap) {
                $qty = (float) ($inQtyMap[$product->id] ?? 0);
                $cost = (float) ($product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0);

                return $qty * $cost;
            })
            ->addColumn('out_qty', function ($product) use ($outQtyMap) {
                return (float) ($outQtyMap[$product->id] ?? 0);
            })
            ->addColumn('out_value', function ($product) use ($outQtyMap) {
                $qty = (float) ($outQtyMap[$product->id] ?? 0);
                $cost = (float) ($product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0);

                return $qty * $cost;
            })
            ->addColumn('onhand_qty', function ($product) use ($onHandQtyMap) {
                return (float) ($onHandQtyMap[$product->id] ?? 0);
            })
            ->addColumn('onhand_value', function ($product) use ($onHandQtyMap) {
                $qty = (float) ($onHandQtyMap[$product->id] ?? 0);
                $cost = (float) ($product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0);

                return $qty * $cost;
            })
            ->make(true);
    }
}
