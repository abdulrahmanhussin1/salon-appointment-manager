<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\DataTables\PaymentMethodDataTable;
use App\Http\Requests\PaymentMethodRequest;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PaymentMethodDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.settings.payment_methods.index');
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
    public function store(PaymentMethodRequest $request)
    {
        PaymentMethod::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' =>auth()->id()
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->route('payment_methods.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.pages.settings.payment_methods.edit', compact('paymentMethod'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'updated_by'=>auth()->id()
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('payment_methods.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
