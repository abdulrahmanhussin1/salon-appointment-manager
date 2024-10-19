<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\DataTables\ProductCategoryDataTable;
use App\Http\Requests\ProductCategoryRequest;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductCategoryDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.products.product_categories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductCategoryRequest $request)
    {
        ProductCategory::create([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'created_by'=>auth()->id()
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();

    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductCategory $productCategory)
    {
        return view('admin.pages.products.product_categories.edit', compact('productCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductCategoryRequest $request, ProductCategory $productCategory)
    {
        $productCategory->update([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'updated_by'=>auth()->id()
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('product_categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
