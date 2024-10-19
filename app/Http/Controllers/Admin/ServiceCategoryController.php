<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\DataTables\ServiceCategoryDataTable;
use App\Http\Requests\ServiceCategoryRequest;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ServiceCategoryDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.services.service_categories.index');
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
    public function store(ServiceCategoryRequest $request)
    {

        ServiceCategory::create([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'created_by'=>auth()->id()
        ]);

        Alert::success(__('Success'),__('Create Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceCategory $serviceCategory)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceCategory $serviceCategory)
    {
        return view('admin.pages.services.service_categories.edit', compact('serviceCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $serviceCategory->update([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'updated_by'=>auth()->id()
        ]);

        Alert::success(__('Success'),__('Update Successfully'));
        return redirect()->route('service_categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
