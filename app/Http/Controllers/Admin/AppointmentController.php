<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Unit;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Carbon\Traits\Units;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\DataTables\ProductDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $Appointments = Appointment::all();
        return response()->json( AppointmentResource::collection($Appointments) ) ;

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->select('id', 'name')->get();
        $units = Unit::where('status', 'active')->select('id', 'name')->get();
        $productCategories = ProductCategory::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();

        return view('admin.pages.products.products.create_edit', compact('suppliers', 'units', 'productCategories','branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Appointment::create([
            'customer_id' => $request->customer_id,
            'provider_id' => $request->provider_id,
            'service_id' => $request->service_id,
            'start_date' => Carbon::parse( $request->start_date)->format('Y-m-d H:i:s'),
            'end_date' => Carbon::parse( $request->end_date)->format('Y-m-d H:i:s'),
            'created_by' => auth()->id(),
        ]);

        Alert::success(__(key: 'Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        abort(404);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product= Appointment::findOrFail( $request->id ) ;

        $product->update([
            'customer_id' => $request->customer_id,
            'provider_id' => $request->provider_id,
            'service_id' => $request->service_id,
            'start_date' => Carbon::parse( $request->start_date)->format('Y-m-d H:i:s'),
            'end_date' => Carbon::parse( $request->end_date)->format('Y-m-d H:i:s'),
            'updated_by' => auth()->id(),
        ]);
        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $product= Appointment::findOrFail( $request->id ) ;
        $product->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
        return redirect()->back();

    }
}
