<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InventoryTransactionController extends Controller
{

    public function TransferInView()
    {
        $inventories = Inventory::where('status', 'active')->select('id','name')->get();
        $products = Product::where('status', 'active')->select('id','name')->get();
        return view('admin.pages.inventories.transactions.transfer_in', compact('inventories', 'products'));
    }

    public function TransferIn(Request $request)
    {
        //
    }



    public function TransferOutView()
    {
        return view('admin.pages.inventories.transactions.transfer_out');
    }

    public function TransferOut(Request $request, string $id)
    {
        //
    }

}
