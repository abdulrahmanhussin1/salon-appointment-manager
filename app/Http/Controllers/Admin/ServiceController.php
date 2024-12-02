<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tool;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Employee;
use App\Traits\AppHelper;
use App\Models\ServiceTool;
use Illuminate\Http\Request;
use App\Models\ServiceProduct;
use App\Models\ServiceCategory;
use App\Models\ServiceEmployee;
use Illuminate\Support\Facades\DB;
use App\DataTables\ServiceDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

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
        $serviceCategories = ServiceCategory::select('id', 'name')->where('status', 'active')->get();
        $employees = Employee::select('id', 'name')->where('status', 'active')->get();
        $tools = Tool::select('id', 'name')->where('status', 'active')->get();
        $products = Product::select('id', 'name')->where('status', 'active')->get();
        $branches = Branch::select('id', 'name')->where('status', 'active')->get();
        return view('admin.pages.services.services.create_edit', compact('serviceCategories', 'employees', 'tools', 'products','branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceRequest $request)
    {
        try {
            $image = null;
            if ($request->hasFile('image')) {
                $image = AppHelper::handleFileUpload($request, 'image', "uploads/images/services", null);
            }

            DB::beginTransaction();
            $service = Service::create([
                'name' => $request->name,
                'notes' => $request->notes,
                'price' => $request->price,
                'service_category_id' => $request->service_category_id,
                'is_target' => $request->is_target,
                'outside_price' => $request->outside_price ?? 0,
                'status' => $request->status,
                'duration' => $request->duration ?? 0,
                'created_by' => auth()->user()->id,
                'image' => $image,
                'branch_id' => $request->branch_id,
            ]);

            // Attach tools
            if ($request->has('tool_id')) {
                foreach ($request->tool_id as $toolId) {
                    ServiceTool::create([
                        'service_id' => $service->id,
                        'tool_id' => $toolId,
                    ]);
                }
            }

            // Attach products
            if ($request->has('product_id')) {
                foreach ($request->product_id as $productId) {
                    ServiceProduct::create([
                        'service_id' => $service->id,
                        'product_id' => $productId,
                    ]);
                }
            }

            // Attach employees
            if ($request->has('employee_id')) {
                $employees = $request->input('employee_id');
                foreach ($employees as $employeeId) {
                    ServiceEmployee::create([
                        'service_id' => $service->id,
                        'employee_id' => $employeeId,
                        'commission_type' => $request->input("commission_type.$employeeId"),
                        'commission_value' => $request->input("commission_value.$employeeId"),
                        'is_immediate_commission' => $request->input("is_immediate_commission.$employeeId", false),
                    ]);
                }
            }

            DB::commit();
            Alert::success(__('Success'), __('Create Successfully'));
            return redirect()->route('services.create');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error(__('error'), __('error in create service , please try again'));
        }
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
        $serviceCategories = ServiceCategory::select('id', 'name')->where('status', 'active')->get();
        $employees = Employee::select('id', 'name')->where('status', 'active')->get();
        $tools = Tool::select('id', 'name')->where('status', 'active')->get();
        $products = Product::select('id', 'name')->where('status', 'active')->get();
        $branches = Branch::select('id', 'name')->where('status', 'active')->get();
        return view('admin.pages.services.services.create_edit', compact('service', 'serviceCategories', 'employees', 'tools', 'products','branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceRequest $request, Service $service)
    {
        try {
            $image = $service->image;
            if ($request->hasFile('image')) {
                $image = AppHelper::handleFileUpload($request, 'image', "uploads/images/services", null);
            }
            DB::beginTransaction();
            $service->update([
                'name' => $request->name,
                'notes' => $request->notes,
                'price' => $request->price,
                'service_category_id' => $request->service_category_id,
                'is_target' => $request->is_target,
                'outside_price' => $request->outside_price ?? 0,
                'status' => $request->status,
                'duration' => $request->duration ?? 0,
                'image' => $image,
                'branch_id' => $request->branch_id,
                'updated_by' => auth()->user()->id,
            ]);
            // Detach previous associations
            ServiceTool::where('service_id', $service->id)->delete();
            ServiceProduct::where('service_id', $service->id)->delete();
            ServiceEmployee::where('service_id', $service->id)->delete();

            // Attach new associations
            if ($request->has('tool_id')) {
                foreach ($request->tool_id as $toolId) {
                    ServiceTool::create([
                        'service_id' => $service->id,
                        'tool_id' => $toolId,
                    ]);
                }
            }

            if ($request->has('product_id')) {
                foreach ($request->product_id as $productId) {
                    ServiceProduct::create([
                        'service_id' => $service->id,
                        'product_id' => $productId,
                    ]);
                }
            }
            if ($request->has('employee_id')) {
                $employees = $request->input('employee_id');
                foreach ($employees as $employeeId) {
                    ServiceEmployee::create([
                        'service_id' => $service->id,
                        'employee_id' => $employeeId,
                        'commission_type' => $request->input("commission_type.$employeeId"),
                        'commission_value' => $request->input("commission_value.$employeeId"),
                        'is_immediate_commission' => $request->input("is_immediate_commission.$employeeId", false),
                    ]);
                }
            }
            DB::commit();
            Alert::success(__('Success'), __('Update Successfully'));
            return redirect()->route('services.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error(__('error'), __('error in update service , please try again'));
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        if ($service->image && Storage::exists($service->image)) {
            Storage::delete($service->image);
        }
        ServiceTool::where('service_id', $service->id)->delete();
        ServiceProduct::where('service_id', $service->id)->delete();
        ServiceEmployee::where('service_id', $service->id)->delete();
        $service->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }

}
