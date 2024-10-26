<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tool;
use App\Models\Product;
use App\Models\Service;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\DataTables\ServiceDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;

class ServiceController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index(ServiceDataTable $dataTable)
    {


        return $dataTable->render('admin.pages.services.services.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $serviceCategories = ServiceCategory::select('id','name')->where('status', 'active')->get();
        $employees = Employee::select('id','name')->where('status', 'active')->get();
        $tools = Tool::select('id','name')->where('status', 'active')->get();
        $products = Product::select('id','name')->where('status', 'active')->get();
        return view('admin.pages.services.services.create_edit', compact( 'serviceCategories', 'employees', 'tools', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        $serviceCategories = ServiceCategory::select('id','name')->where('status', 'active')->get();
        $employees = Employee::select('id','name')->where('status', 'active')->get();
        $tools = Tool::select('id','name')->where('status', 'active')->get();
        $products = Product::select('id','name')->where('status', 'active')->get();

        return view('admin.pages.services.services.create_edit', compact('service','serviceCategories', 'employees', 'tools', 'products'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceRequest $request, Service $service)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        //
    }
}
