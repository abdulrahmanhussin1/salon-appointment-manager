<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;

class StockReportController extends Controller
{
    public function index()
    {
        $inventories = Inventory::where('status', 'active')->get();
        return view('admin.pages.reports.stocke_report', compact('inventories'));
    }

    public function getData(Request $request)
    {
        $query = Product::with(['supplierPrices', 'inventoryProducts'])
        ->select('products.*')
        ->when($request->inventory_id, function ($query) use ($request) {
            $query->join('inventory_products', 'products.id', '=', 'inventory_products.product_id')
            ->where('inventory_products.inventory_id', $request->inventory_id)
                ->addSelect('inventory_products.quantity as stock_quantity');
        });

        return DataTables::of($query)
            ->addColumn('current_stock', function ($product) use ($request) {
                if ($request->inventory_id) {
                    return $product->stock_quantity ?? 0;
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
            ->addColumn('total_value', function ($product) use ($request) {
                $quantity = $request->inventory_id ?
                    ($product->stock_quantity ?? 0) :
                    $product->inventoryProducts->sum('quantity');

                $latestPrice = $product->supplierPrices()
                    ->latest()
                    ->first();
                $price = $latestPrice ? $latestPrice->supplier_price : 0;

                return $quantity * $price;
            })
            ->make(true);
    }
}
