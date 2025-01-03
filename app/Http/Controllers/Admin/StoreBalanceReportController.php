<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Models\InventoryProduct;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;

class StoreBalanceReportController extends Controller
{
    public function index()
    {
        $inventories = Inventory::where('status', 'active')->get();
        return view('admin.pages.reports.stocke_balance_report', compact('inventories'));
    }

    public function getData(Request $request)
    {
        $date = Carbon::now();
        $firstDayOfMonth = $date->startOfMonth();
        $lastDayOfMonth = $date->copy()->endOfMonth();

        $query = Product::with(['supplierPrices'])
        ->select('products.*')
        ->when($request->inventory_id, function ($query) use ($request) {
            $query->where('branch_id', $request->inventory_id);
        });

        return DataTables::of($query)
            ->addColumn('unit_cost', function ($product) {
                return $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;
            })
            ->addColumn('beginning_qty', function ($product) use ($firstDayOfMonth) {
                return InventoryProduct::where('product_id', $product->id)
                    ->where('created_at', '<', $firstDayOfMonth)
                    ->sum('quantity');
            })
            ->addColumn('beginning_value', function ($product) use ($firstDayOfMonth) {
                $qty = InventoryProduct::where('product_id', $product->id)
                    ->where('created_at', '<', $firstDayOfMonth)
                    ->sum('quantity');
            $cost = $product->supplierPrices->sortBy('created_at')->first()->supplier_price ?? 0;
                return $qty * $cost;
            })
            ->addColumn('in_qty', function ($product) use ($firstDayOfMonth, $lastDayOfMonth) {
                return InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) {
                    $query->where('transaction_type', 'purchase');
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');
            })
            ->addColumn('in_value', function ($product) use ($firstDayOfMonth, $lastDayOfMonth) {
                $qty = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) {
                    $query->where('transaction_type', 'purchase');
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');

                $cost = $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;
                return $qty * $cost;
            })
            ->addColumn('out_qty', function ($product) use ($firstDayOfMonth, $lastDayOfMonth) {
                return InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) {
                    $query->where('transaction_type', 'sales');
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');
            })
            ->addColumn('out_value', function ($product) use ($firstDayOfMonth, $lastDayOfMonth) {
                $qty = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) {
                    $query->where('transaction_type', 'sales');
                })
                    ->where('product_id', $product->id)
                    ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->sum('quantity');

                $cost = $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;
                return $qty * $cost;
            })
            ->addColumn('onhand_qty', function ($product) {
                return InventoryProduct::where('product_id', $product->id)->sum('quantity');
            })
            ->addColumn('onhand_value', function ($product) {
                $qty = InventoryProduct::where('product_id', $product->id)->sum('quantity');
                $cost = $product->supplierPrices->sortByDesc('created_at')->first()->supplier_price ?? 0;
                return $qty * $cost;
            })
            ->make(true);
    }
}
