<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Traits\HasBranchFilter;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class StockReportController extends Controller
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

        return view('admin.pages.reports.stocke_report', compact('inventories', 'branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function getData(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $query = Product::with(['supplierPrices', 'inventoryProducts'])
            ->select('products.*')
            ->when($effectiveBranchId, function ($query) use ($effectiveBranchId) {
                $query->where('products.branch_id', $effectiveBranchId);
            })
            ->when($request->inventory_id, function ($query) use ($request) {
                $query->join('inventory_products', 'products.id', '=', 'inventory_products.product_id')
                    ->where('inventory_products.inventory_id', $request->inventory_id)
                    ->addSelect('inventory_products.quantity as stock_quantity');
            });

        return DataTables::of($query)
            ->addColumn('current_stock', function ($product) use ($request, $effectiveBranchId) {
                if ($request->inventory_id) {
                    return $product->stock_quantity ?? 0;
                }

                if ($effectiveBranchId) {
                    return $product->inventoryProducts()
                        ->whereHas('inventory', fn ($q) => $q->where('branch_id', $effectiveBranchId))
                        ->sum('quantity') ?? 0;
                }

                return $product->inventoryProducts->sum('quantity') ?? 0;
            })
            ->addColumn('supplier_price', function ($product) {
                // Get the most recent supplier price
                $latestPrice = $product->supplierPrices()
                    ->latest()
                    ->first();

                return $latestPrice ? $latestPrice->supplier_price : 0;
            })
            ->addColumn('total_value', function ($product) use ($request, $effectiveBranchId) {
                if ($request->inventory_id) {
                    $quantity = $product->stock_quantity ?? 0;
                } elseif ($effectiveBranchId) {
                    $quantity = $product->inventoryProducts()
                        ->whereHas('inventory', fn ($q) => $q->where('branch_id', $effectiveBranchId))
                        ->sum('quantity') ?? 0;
                } else {
                    $quantity = $product->inventoryProducts->sum('quantity') ?? 0;
                }

                $latestPrice = $product->supplierPrices()
                    ->latest()
                    ->first();
                $price = $latestPrice ? $latestPrice->supplier_price : 0;

                return $quantity * $price;
            })
            ->make(true);
    }
}
