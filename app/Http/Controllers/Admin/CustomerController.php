<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\CustomerDataTable;
use App\Http\Requests\CustomerRequest;
use RealRashid\SweetAlert\Facades\Alert;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CustomerDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.customers.index');
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
    public function store(CustomerRequest $request)
    {
        $customer = Customer::create([
            'name' => $request->name,
            'salutation' => $request->salutation,
            'status' => $request->status ?? 'active',
            'gender' => $request->gender,
            'deposit' => $request->deposit ?? 0,
            'added_from' => $request->added_from,
            'dob' => $request->dob,
            'notes' => $request->notes,
            'is_vip' => $request->is_vip,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'created_by' => auth()->id()
        ]);

        if($request->ajax())
        {
            return response()->json([
                'success' => true,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_deposit' => $customer->deposit,
        ],201);
        }
        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('admin.pages.customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $customer->update([
            'name' => $request->name,
            'salutation' => $request->salutation,
            'status' => $request->status ?? 'active',
            'gender' => $request->gender,
            'added_from' => $request->added_from,
            'dob' => $request->dob,
            'deposit' => $request->deposit ?? 0,
            'notes' => $request->notes,
            'is_vip' => $request->is_vip,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'updated_by' => auth()->id()
        ]);
        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('customers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
