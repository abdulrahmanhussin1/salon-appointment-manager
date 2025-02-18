<?php

namespace App\Http\Controllers\Admin;

use App\Models\Unit;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Traits\Units;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\DataTables\ProductDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductDataTable $dataTable)
    {
        return $dataTable->render('admin.pages..products.products.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->select('id', 'name')->get();
        $units = Unit::where('status', 'active')->select('id', 'name')->get();
        $productCategories = ProductCategory::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();

        return view('admin.pages.products.products.create_edit', compact('suppliers', 'units', 'productCategories','branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $image = NULL;
        if ($request->hasFile('image')) {
            $image = Storage::putFileAs("uploads/images/products", $request->image, now()->format('Y-m-d') . '_' . str_replace(' ', '_', $request->name) . '_image.' . $request->image->getClientOriginalExtension());
        }

        Product::create([
            'name' => $request->name,
            'description' => $request->description,
            // 'supplier_price' => $request->supplier_price,
            // 'customer_price' => $request->customer_price,
            //'outside_price' => $request->outside_price,
            'unit_id' => $request->unit_id,
            'supplier_id' => $request->supplier_id,
            'category_id' => $request->category_id,
            'is_target' => $request->is_target,
            'price_can_change' => $request->price_can_change,

            'initial_quantity' => $request->initial_quantity,
            'type' => $request->type,
            'status' => $request->status,
            'image' => $image,
            'branch_id' => $request->branch_id,
            'created_by' => auth()->id(),
        ]);

        Alert::success(__(key: 'Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $suppliers = Supplier::where('status', 'active')->select('id', 'name')->get();
        $units = Unit::where('status', 'active')->select('id', 'name')->get();
        $productCategories = ProductCategory::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();

        return view('admin.pages.products.products.create_edit', compact('product', 'suppliers', 'units', 'productCategories', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $image = $product->image;
        if ($request->hasFile('image')) {
            if ($product->image && Storage::exists($product->image)) {
                Storage::delete($product->image);
            }
            $image = Storage::putFileAs("uploads/images/products", $request->image, now()->format('Y-m-d') . '_' . str_replace(' ', '_', $request->name) . '_image.' . $request->image->getClientOriginalExtension());

        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            // 'supplier_price' => $request->supplier_price,
            // 'customer_price' => $request->customer_price,
            //'outside_price' => $request->outside_price,
            'unit_id' => $request->unit_id,
            'supplier_id' => $request->supplier_id,
            'category_id' => $request->category_id,
            'initial_quantity' => $request->initial_quantity ?? 0,
            'is_target' => $request->is_target,
            'price_can_change' => $request->price_can_change,

            'type' => $request->type,
            'status' => $request->status,
            'image' => $image,
            'branch_id' => $request->branch_id,
            'updated_by' => auth()->id(),
        ]);
        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('products.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->image && Storage::exists($product->image)) {
            Storage::delete($product->image);
        }
        $product->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
