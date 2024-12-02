<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\DataTables\ExpenseDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use RealRashid\SweetAlert\Facades\Alert;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ExpenseDataTable $dataTable)
    {
        $expenseTypes = ExpenseType::where('status','active')->select('id','name')->get();
        $paymentMethods = PaymentMethod::where('status','active')->select('id','name')->get();
        $branches = Branch::where('status','active')->select('id','name')->get();
        return $dataTable->render('admin.pages.expenses.index',compact('expenseTypes','paymentMethods','branches'));
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
    public function store(ExpenseRequest $request)
    {
        Expense::create([
            'expense_type_id' => $request->expense_type_id,
            'description' => $request->description,
            'amount' => $request->amount ?? 0,
            'paid_at' => $request->paid_at ?? now(),
            'paid_amount' => $request->paid_amount ?? 0,
            'balance' => $request->amount - $request->paid_amount ?? 0,
            'invoice_number' => $request->invoice_number,
            'payment_method_id' => $request->payment_method_id,
            'branch_id' => $request->branch_id,
            'status' => $request->status,
            'created_by' => auth()->id()
        ]);
        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        $expenseTypes = ExpenseType::where('status', 'active')->select('id', 'name')->get();
        $paymentMethods = PaymentMethod::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();

        return view('admin.pages.expenses.edit', compact('expense','paymentMethods','expenseTypes','branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $expense->update([
            'expense_type_id' => $request->expense_type_id,
            'description' => $request->description,
            'amount' => $request->amount ?? 0,
            'paid_at' => $request->paid_at ?? now(),
            'paid_amount' => $request->paid_amount ?? 0,
            'balance' => $request->amount - $request->paid_amount ?? 0,
            'invoice_number' => $request->invoice_number,
            'payment_method_id' => $request->payment_method_id,
            'branch_id' => $request->branch_id,
            'status' => $request->status,
            'updated_by' => auth()->id()
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('expenses.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
