<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $appointments = Appointment::all();

        return response()->json(AppointmentResource::collection($appointments));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('home.calender');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AppointmentRequest $request)
    {
        Appointment::create([
            'customer_id' => $request->validated('customer_id'),
            'provider_id' => $request->validated('provider_id'),
            'service_id' => $request->validated('service_id'),
            'start_date' => Carbon::parse($request->validated('start_date'))->format('Y-m-d H:i:s'),
            'end_date' => Carbon::parse($request->validated('end_date'))->format('Y-m-d H:i:s'),
            'created_by' => auth()->id(),
        ]);

        Alert::success(__('Success'), __('Created Successfully'));

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AppointmentRequest $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $appointment->update([
            'customer_id' => $request->validated('customer_id'),
            'provider_id' => $request->validated('provider_id'),
            'service_id' => $request->validated('service_id'),
            'start_date' => Carbon::parse($request->validated('start_date'))->format('Y-m-d H:i:s'),
            'end_date' => Carbon::parse($request->validated('end_date'))->format('Y-m-d H:i:s'),
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
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        Alert::success(__('Success'), __('Deleted Successfully'));

        return redirect()->back();
    }
}
