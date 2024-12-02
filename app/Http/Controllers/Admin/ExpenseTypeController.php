<?php

namespace App\Http\Controllers\Admin;

use App\Models\ExpenseType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\ExpenseTypeDataTable;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Requests\ExpenseTypeRequest;

class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ExpenseTypeDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.expenses.expense_types.index');
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
    public function store(ExpenseTypeRequest $request)
    {
        ExpenseType::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => auth()->id()
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseType $expenseType)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseType $expenseType)
    {
        return view('admin.pages.expenses.expense_types.edit', compact('expenseType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpenseTypeRequest $request, ExpenseType $expenseType)
    {
        $expenseType->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'updated_by' => auth()->id()
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('expense_types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseType $expenseType)
    {
        $expenseType->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
