<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\CustomerTransaction;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;

class CustomerTransactionController extends Controller
{
    public function getCustomerPayments()
    {
        $customers = Customer::where('status', 'active')->select('id', 'name')->get();
        return view('admin.pages.customers.transactions.payments.index', compact('customers'));
    }

    public function storeCustomerPayment(Request $request)
    {
        $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('status', 'active')
            ],
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        CustomerTransaction::create([
            'customer_id' => $request->customer_id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => $request->amount,
            'notes' => $request->notes ?? 'deposit',
            'created_by' => auth()->id(),
        ]);
        Alert::success(__('Success'), __('Payment Received Successfully'));

        return redirect()->route('customer_transactions.get_customer_payments');
    }
}
