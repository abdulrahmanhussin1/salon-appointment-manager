<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $appointments = Appointment::with(['customer', 'provider', 'service'])->get();

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
        $startDate = Carbon::parse($request->validated('start_date'))->format('Y-m-d H:i:s');
        $providerId = (int) $request->validated('provider_id');
        $serviceId = (int) $request->validated('service_id');

        $endDate = $request->validated('end_date')
            ? Carbon::parse($request->validated('end_date'))->format('Y-m-d H:i:s')
            : null;

        if (! $endDate) {
            $service = \App\Models\Service::findOrFail($serviceId);
            $endDate = Carbon::parse($startDate)->addMinutes($service->duration_minutes)->format('Y-m-d H:i:s');
        }

        $status = $request->validated('status', AppointmentStatus::REQUESTED->value) ?? AppointmentStatus::REQUESTED->value;

        $appointment = DB::transaction(function () use ($request, $startDate, $endDate, $providerId, $serviceId, $status) {
            // Pessimistically lock provider to prevent concurrent double-booking
            Employee::where('id', $providerId)->lockForUpdate()->first();

            if (Appointment::findConflict($providerId, $startDate, $endDate)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_id' => __('The selected provider already has an appointment scheduled during this time slot.'),
                ]);
            }

            return Appointment::create([
                'customer_id' => $request->validated('customer_id'),
                'provider_id' => $providerId,
                'service_id' => $serviceId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'created_by' => auth()->id(),
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Created Successfully'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ], 201);
        }

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

        $startDate = Carbon::parse($request->validated('start_date'))->format('Y-m-d H:i:s');
        $providerId = (int) $request->validated('provider_id');
        $serviceId = (int) $request->validated('service_id');

        $endDate = $request->validated('end_date')
            ? Carbon::parse($request->validated('end_date'))->format('Y-m-d H:i:s')
            : null;

        if (! $endDate) {
            $service = \App\Models\Service::findOrFail($serviceId);
            $endDate = Carbon::parse($startDate)->addMinutes($service->duration_minutes)->format('Y-m-d H:i:s');
        }

        $appointment = DB::transaction(function () use ($request, $id, $startDate, $endDate, $providerId, $serviceId) {
            $appointment = Appointment::where('id', $id)->lockForUpdate()->firstOrFail();
            Employee::where('id', $providerId)->lockForUpdate()->first();

            if (Appointment::findConflict($providerId, $startDate, $endDate, $appointment->id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_id' => __('The selected provider already has an appointment scheduled during this time slot.'),
                ]);
            }

            // If status change is requested in payload, validate transition first
            if ($request->has('status') && $request->validated('status')) {
                $newStatus = AppointmentStatus::from($request->validated('status'));
                if ($appointment->status !== $newStatus) {
                    $appointment->transitionTo($newStatus, $request->input('cancellation_reason'));
                }
            }

            $appointment->update([
                'customer_id' => $request->validated('customer_id'),
                'provider_id' => $providerId,
                'service_id' => $serviceId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'updated_by' => auth()->id(),
            ]);

            return $appointment;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Updated Successfully'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Updated Successfully'));

        return redirect()->back();
    }

    /**
     * Confirm appointment.
     */
    public function confirm(Request $request, $id)
    {
        $appointment = DB::transaction(function () use ($id) {
            $appointment = Appointment::where('id', $id)->lockForUpdate()->firstOrFail();
            Employee::where('id', $appointment->provider_id)->lockForUpdate()->first();

            if (Appointment::findConflict($appointment->provider_id, $appointment->start_date, $appointment->end_date, $appointment->id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_id' => __('Cannot confirm appointment: The provider already has an active appointment scheduled during this time slot.'),
                ]);
            }

            $appointment->transitionTo(AppointmentStatus::CONFIRMED);

            return $appointment;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment confirmed successfully.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment confirmed successfully.'));

        return redirect()->back();
    }

    /**
     * Cancel appointment with reason.
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->transitionTo(AppointmentStatus::CANCELLED, $request->input('cancellation_reason'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment cancelled successfully.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment cancelled successfully.'));

        return redirect()->back();
    }

    /**
     * Check-in customer for appointment.
     */
    public function checkIn(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->transitionTo(AppointmentStatus::CHECKED_IN);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment checked-in successfully.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment checked-in successfully.'));

        return redirect()->back();
    }

    /**
     * Start delivery of service for appointment.
     */
    public function startService(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->transitionTo(AppointmentStatus::IN_SERVICE);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment marked as in-service.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment marked as in-service.'));

        return redirect()->back();
    }

    /**
     * Mark appointment service as completed.
     */
    public function complete(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->transitionTo(AppointmentStatus::COMPLETED);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment completed successfully.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment completed successfully.'));

        return redirect()->back();
    }

    /**
     * Mark appointment as customer no-show.
     */
    public function noShow(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->transitionTo(AppointmentStatus::NO_SHOW);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment marked as no-show.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment marked as no-show.'));

        return redirect()->back();
    }

    /**
     * Generic status transition endpoint.
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', new \Illuminate\Validation\Rules\Enum(AppointmentStatus::class)],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = Appointment::findOrFail($id);
        $targetStatus = AppointmentStatus::from($request->input('status'));

        $appointment->transitionTo($targetStatus, $request->input('cancellation_reason'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Appointment status updated successfully.'),
                'data' => new AppointmentResource($appointment->load(['customer', 'provider', 'service'])),
            ]);
        }

        Alert::success(__('Success'), __('Appointment status updated successfully.'));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        // Only requested appointments can be deleted (BR-A004 / BR-A005 lifecycle rules)
        if ($appointment->status !== AppointmentStatus::REQUESTED) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Only requested appointments can be deleted. Please cancel confirmed or active appointments instead.'),
                ], 422);
            }

            Alert::error(__('Error'), __('Only requested appointments can be deleted. Please cancel confirmed or active appointments instead.'));

            return redirect()->back();
        }

        $appointment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Deleted Successfully'),
            ], 200);
        }

        Alert::success(__('Success'), __('Deleted Successfully'));

        return redirect()->back();
    }
}
