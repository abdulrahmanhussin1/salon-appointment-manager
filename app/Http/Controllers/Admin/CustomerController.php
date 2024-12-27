<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\Log;
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
        try {
            DB::beginTransaction();

            // Create the customer record
            $customer = Customer::create([
                'name' => $request->name,
                'salutation' => $request->salutation,
                'status' => $request->status ?? 'active',
                'gender' => $request->gender,
                'added_from' => $request->added_from,
                'dob' => $request->dob,
                'notes' => $request->notes,
                'is_vip' => $request->is_vip,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'created_by' => auth()->id(),
            ]);

            // Handle deposits if applicable
            if ($request->ajax() && $request->deposit && $request->deposit > 0) {
                CustomerTransaction::create([
                    'customer_id' => $customer->id,
                    'reference_type' => 'deposit',
                    'reference_id' => 0, // Set a meaningful reference ID if applicable
                    'amount' => $request->deposit,
                    'notes' => 'Initial deposit created with customer',
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            // Return appropriate response for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'customer_deposit' => $customer->customerTransactions()
                        ->where('reference_type', 'deposit')
                        ->latest()
                        ->first()
                        ->amount ?? 0,
                ], 201);
            }

            // Show success alert for non-AJAX requests
            Alert::success(__('Success'), __('Customer created successfully'));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error details for debugging
            Log::error('Error creating customer: ' . $e->getMessage());

            // Handle response for AJAX and non-AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create customer. Please try again.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', __('Failed to create customer. Please try again.'));
        }
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
    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update([
            'name' => $request->name,
            'salutation' => $request->salutation,
            'status' => $request->status ?? 'active',
            'gender' => $request->gender,
            'added_from' => $request->added_from,
            'dob' => $request->dob,
            //'deposit' => $request->deposit ?? 0,
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
