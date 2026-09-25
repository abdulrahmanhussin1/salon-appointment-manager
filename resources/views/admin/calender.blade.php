@extends('admin.layouts.app')
@section('title')
    {{ __('Calender') }}
@endsection
@section('content')
    <script src="{{ asset('admin-assets/assets/vendor/fullcalendar-6.1.15/dist/index.global.min.js') }}"></script>
    @if (app()->getLocale() === 'ar')
        <script src="{{ asset('admin-assets/assets/vendor/fullcalendar-6.1.15/packages/core/locales/ar.global.min.js') }}"></script>
    @endif

    <x-breadcrumb :pageName="__('Home')">
        <x-breadcrumb-item>{{ __('Home') }}</x-breadcrumb-item>
    </x-breadcrumb>
    <div class="ms-2 row">
        <div class="card-title col-8">{{ __('Customer Details') }}</div>
        <div class="col-4 text-end mt-3 pe-4">
            @if (App\Traits\AppHelper::perUSer('customers.create'))
                <x-modal-button :title="__('Customer')" target="customerModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
    </div>
    <div class="ms-2 row">
        <div class="card-title col-8">{{ __('Appointment Details') }}</div>
        <div class="col-4 text-end mt-3 pe-4">
            <x-modal-button :title="__('Appointment')" target="appoentmentModal"><i
                    class="bi bi-plus-lg me-2"></i></x-modal-button>
        </div>
    </div>




    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-2 small">
                <span class="fw-bold text-muted me-1"><i class="bi bi-palette me-1"></i>{{ __('Legend') }}:</span>
                <span class="badge" style="background-color: #ffc107; color: #212529;">{{ __('Requested') }}</span>
                <span class="badge" style="background-color: #0d6efd; color: #ffffff;">{{ __('Confirmed') }}</span>
                <span class="badge" style="background-color: #6f42c1; color: #ffffff;">{{ __('Checked In') }}</span>
                <span class="badge" style="background-color: #fd7e14; color: #ffffff;">{{ __('In Service') }}</span>
                <span class="badge" style="background-color: #198754; color: #ffffff;">{{ __('Completed') }}</span>
                <span class="badge" style="background-color: #6c757d; color: #ffffff;">{{ __('Cancelled') }}</span>
                <span class="badge" style="background-color: #212529; color: #ffffff;">{{ __('No Show') }}</span>
            </div>
        </div>
    </div>

    <div id="calendar"></div>

    <!-- Modal -->

    <x-modal id="eventModal" :title="__('Appointment Details')">

        <div class="px-3 pt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">{{ __('Current Status') }}:</span>
                <span id="event_status_badge" class="badge"></span>
            </div>
            <div id="event_cancellation_info" class="alert alert-secondary d-none text-start py-2 small mb-2">
                <strong>{{ __('Cancellation Reason') }}:</strong> <span id="event_cancelled_reason"></span>
            </div>

            <!-- Lifecycle Quick Actions -->
            <div id="statusActionsContainer" class="d-flex flex-wrap gap-2 justify-content-center my-2 p-2 bg-light rounded border">
                <button type="button" id="btnConfirm" class="btn btn-sm btn-primary action-btn d-none" onclick="triggerStatusAction('confirm')">
                    <i class="bi bi-check-circle me-1"></i>{{ __('Confirm') }}
                </button>
                <button type="button" id="btnCheckIn" class="btn btn-sm btn-info text-white action-btn d-none" onclick="triggerStatusAction('check-in')">
                    <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Check In') }}
                </button>
                <button type="button" id="btnStartService" class="btn btn-sm btn-warning action-btn d-none" onclick="triggerStatusAction('start-service')">
                    <i class="bi bi-play-circle me-1"></i>{{ __('Start Service') }}
                </button>
                <button type="button" id="btnComplete" class="btn btn-sm btn-success action-btn d-none" onclick="triggerStatusAction('complete')">
                    <i class="bi bi-check2-all me-1"></i>{{ __('Complete') }}
                </button>
                <button type="button" id="btnCheckout" class="btn btn-sm btn-success text-white action-btn d-none" onclick="proceedToCheckout()">
                    <i class="bi bi-cart-check me-1"></i>{{ __('Checkout / Invoice') }}
                </button>
                <a href="#" id="btnViewInvoice" class="btn btn-sm btn-outline-primary action-btn d-none" target="_blank">
                    <i class="bi bi-receipt me-1"></i>{{ __('View Invoice') }}
                </a>
                <button type="button" id="btnNoShow" class="btn btn-sm btn-dark action-btn d-none" onclick="triggerStatusAction('no-show')">
                    <i class="bi bi-person-x me-1"></i>{{ __('No Show') }}
                </button>
                <button type="button" id="btnCancel" class="btn btn-sm btn-outline-danger action-btn d-none" onclick="openCancelPrompt()">
                    <i class="bi bi-x-circle me-1"></i>{{ __('Cancel') }}
                </button>
            </div>
        </div>

        <form class="text-center" action="{{ route('appointments.update', 1) }}" method="POST" id="appoentmentFormUpdate"
            enctype="multipart/form-data">
            @csrf
            @method('put')
            <div class="modal-body">
                <input type="hidden" name="id" id="id">
                <div class="row">
                    <div class="col-12">
                        <x-form-select name="customer_id" id="edit_customer_id" label='Customer' required>
                            @foreach (App\Models\Customer::all() as $branch)
                                <option @if (old('customer_id') == $branch->id) selected="selected" @endif
                                    value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>
                    <div class="col-12">
                        <x-form-select name="provider_id" id="edit_provider_id" label='Provider' required>
                            @foreach (App\Models\Employee::all() as $branch)
                                <option @if (old('provider_id') == $branch->id) selected="selected" @endif
                                    value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>
                    <div class="col-12">
                        <x-form-select name="service_id" id="edit_service_id" label='Service' required>
                            @foreach (App\Models\Service::all() as $branch)
                                <option @if (old('service_id') == $branch->id) selected="selected" @endif
                                    value="{{ $branch->id }}" data-duration="{{ $branch->duration_minutes }}">
                                    {{ $branch->name }} ({{ $branch->duration_minutes }} min)
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>


                </div>



                <div class="col-12">
                    <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
                    <input type="datetime-local" name="start_date"
                        class="form-control w-100  @error('start_date') is-invalid @enderror" id="edit_start_date"
                        value="" required>
                    @error('start_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="end_date">{{ __('End Date') }} <small class="text-muted">({{ __('Optional — auto-calculated from service') }})</small></label>
                    <input type="datetime-local" name="end_date"
                        class="form-control w-100  @error('end_date') is-invalid @enderror" id="edit_end_date"
                        value="">
                    @error('end_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

            </div>
            <Button type="submit" class="btn btn-success">{{ __('Update') }}</Button>
        </form>

        <form class="text-center my-3"  action="{{ route('appointments.destroy', 1) }}" method="POST" id="appoentmentFormDelete"
            enctype="multipart/form-data">
            @csrf
            @method('DELETE')
            <input type="hidden" name="id" id="id_destroy">
            <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
        </form>

    </x-modal>


    <x-modal id="customerModal" :title="__('Create Customer')">
        <form action="{{ route('customers.store') }}" method="POST" id="customerForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-2">
                        <x-form-select name='salutation' id="salutation" label="salutation">
                            <option @if (old('salutation') == 'Mr') selected @endif value="Mr">
                                {{ __('Mr') }}</option>
                            <option @if (old('salutation') == 'Ms') selected @endif value="Ms">
                                {{ __('Ms') }}</option>
                            <option @if (old('salutation') == 'Mrs') selected @endif value="Mrs">
                                {{ __('Mrs') }}</option>
                            <option @if (old('salutation') == 'Dr') selected @endif value="Dr">
                                {{ __('Dr') }}</option>
                            <option @if (old('salutation') == 'Eng') selected @endif value="Eng">
                                {{ __('Eng') }}</option>

                        </x-form-select>
                    </div>
                    <div class="col-8">
                        <x-input type='text' value="{{ old('name') }}" label="Name" name='name'
                            placeholder='Customer Name' id="name" oninput="" required />
                    </div>

                    <div class="col-2">
                        <div class="form-check form-switch mt-4 mb-0 pt-2">
                            <!-- Hidden input to handle unchecked state -->
                            <input type="hidden" name="is_vip" value="0">
                            <!-- Checkbox input -->
                            <input class="form-check-input" type="checkbox" role="switch" value="1"
                                name="is_vip" id="flexSwitchCheckDefault" {{ old('is_vip') ? 'checked' : '' }}>
                            <label class="form-check-label" for="flexSwitchCheckDefault">{{ __('VIP') }}</label>
                        </div>
                    </div>
                </div>
                <x-input type="email" value="{{ old('email') }}" label="Email" name='email'
                    placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />
                <x-input type="text" value="{{ old('phone') }}" label="phone" id="phone" name='phone'
                    placeholder="phone  Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                    required />


                <div class="col-12">
                    <label class="form-label" for="dob">{{ __('Date Of Birth') }}</label>
                    <input type="date" name="dob" class="form-control  @error('dob') is-invalid @enderror"
                        id="dob" value="{{ isset($employee) ? $employee->dob : old('dob') }}">
                    @error('dob')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <x-form-description value="{{ old('address') }}" label="address" name='address'
                    placeholder='Customer Address' />
                <x-form-description value="{{ old('notes') }}" label="notes" name='notes' placeholder='Notes' />
                <div class="row">
                    <div class="col-6">
                        <label class="form-label" for="customer-deposit">{{ __('Deposit') }}</label>
                        <input type="text" id="customer-deposit" name="deposit"
                            class="form-control form-control-sm text-end" value="{{ $customer->deposit ?? 0 }}"
                            oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')">
                    </div>
                    <div class="col-6">
                        <x-form-select name='gender' id="gender" label="gender" required>
                            <option @if (old('gender') == 'male') selected @endif value="male">
                                {{ __('Male') }}</option>
                            <option @if (old('gender') == 'female') selected @endif value="female">
                                {{ __('Female') }}</option>
                        </x-form-select>
                    </div>
                    <div class="col-12">
                        <x-form-select name='added_from' id="added_from" label="Added From">
                            <option @if (old('added_from') == 'direct') selected @endif value="direct">
                                {{ __('Direct') }}</option>
                            <option @if (old('added_from') == 'online') selected @endif value="online">
                                {{ __('Online') }}</option>
                            <option @if (old('added_from') == 'advertisement') selected @endif value="advertisement">
                                {{ __('Advertisement') }}</option>
                            <option @if (old('added_from') == 'referral') selected @endif value="referral">
                                {{ __('Referral') }}</option>
                            <option @if (old('added_from') == 'walk_in') selected @endif value="walk_in">
                                {{ __('Walk in') }}</option>

                        </x-form-select>
                    </div>
                </div>

            </div>
            <x-modal-footer />
        </form>
    </x-modal>



    <x-modal id="appoentmentModal" :title="__('Create Appointment')">
        <form action="{{ route('appointments.store') }}" method="POST" id="appoentmentForm"
            enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <x-form-select name="customer_id" id="customer_id" label='Customer' required>
                            @foreach (App\Models\Customer::all() as $branch)
                                <option @if (old('customer_id') == $branch->id) selected="selected" @endif
                                    value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>
                    <div class="col-12">
                        <x-form-select name="provider_id" id="provider_id" label='Provider' required>
                            @foreach (App\Models\Employee::all() as $branch)
                                <option @if (old('provider_id') == $branch->id) selected="selected" @endif
                                    value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>
                    <div class="col-12">
                        <x-form-select name="service_id" id="service_id" label='Service' required>
                            @foreach (App\Models\Service::all() as $branch)
                                <option @if (old('service_id') == $branch->id) selected="selected" @endif
                                    value="{{ $branch->id }}" data-duration="{{ $branch->duration_minutes }}">
                                    {{ $branch->name }} ({{ $branch->duration_minutes }} min)
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>


                </div>



                <div class="col-6">
                    <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
                    <input type="datetime-local" name="start_date"
                        class="form-control  @error('start_date') is-invalid @enderror" id="start_date" value=""
                        required>
                    @error('start_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-6">
                    <label class="form-label" for="end_date">{{ __('End Date') }} <small class="text-muted">({{ __('Optional — auto-calculated') }})</small></label>
                    <input type="datetime-local" name="end_date"
                        class="form-control w-100  @error('end_date') is-invalid @enderror" id="end_date"
                        value="">
                    @error('end_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

            </div>
            <x-modal-footer />
        </form>
    </x-modal>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $("#salutation,#gender,#added_from").select2({
                dropdownParent: $("#customerForm")
            });

            $("#customer_id,#provider_id,#service_id").select2({
                dropdownParent: $("#appoentmentForm")
            });

            $("#edit_customer_id,#edit_provider_id,#edit_service_id").select2({
                dropdownParent: $("#appoentmentFormUpdate")
            });

            function autoCalculateEndTime(startDateId, serviceSelectId, endDateId) {
                var startVal = document.getElementById(startDateId) ? document.getElementById(startDateId).value : null;
                var serviceSelect = document.getElementById(serviceSelectId);
                if (!startVal || !serviceSelect) return;

                var selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                var duration = selectedOption ? parseInt(selectedOption.getAttribute('data-duration') || '30', 10) : 30;

                if (duration > 0) {
                    var startDate = new Date(startVal);
                    if (!isNaN(startDate.getTime())) {
                        var endDate = new Date(startDate.getTime() + duration * 60000);
                        var year = endDate.getFullYear();
                        var month = String(endDate.getMonth() + 1).padStart(2, '0');
                        var day = String(endDate.getDate()).padStart(2, '0');
                        var hours = String(endDate.getHours()).padStart(2, '0');
                        var minutes = String(endDate.getMinutes()).padStart(2, '0');
                        var formatted = `${year}-${month}-${day}T${hours}:${minutes}`;
                        var endInput = document.getElementById(endDateId);
                        if (endInput) {
                            endInput.value = formatted;
                        }
                    }
                }
            }

            $('#service_id').on('change', function() {
                autoCalculateEndTime('start_date', 'service_id', 'end_date');
            });
            $('#start_date').on('change input', function() {
                autoCalculateEndTime('start_date', 'service_id', 'end_date');
            });

            $('#edit_service_id').on('change', function() {
                autoCalculateEndTime('edit_start_date', 'edit_service_id', 'edit_end_date');
            });
            $('#edit_start_date').on('change input', function() {
                autoCalculateEndTime('edit_start_date', 'edit_service_id', 'edit_end_date');
            });



            $('#customerForm').submit(function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var method = form.attr('method');

                $.ajax({
                    url: url,
                    type: method,
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            // Handle successful creation or update
                            if (method === 'POST') {
                                // Create a new option for the newly created customer
                                var newCustomerOption = $('<option>')
                                    .val(response.customer_id)
                                    .text(response.customer_name + ' - ' + response
                                        .customer_phone);
                                $('#customer_id').append(newCustomerOption);

                                // Select the newly created customer
                                $('#customer_id').val(response.customer_id);
                            } else {
                                // Update the existing customer option
                                $('#customer_id option[value="' + response.customer_id + '"]')
                                    .text(response.customer_name + ' - ' + response
                                        .customer_phone);
                            }

                            // Close the modal
                            $('#customerModal').modal('hide');

                            // Display a success message or perform other actions
                            // Display a success message or perform other actions
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('Success') }}',
                                text: '{{ __('Customer saved successfully!') }}'
                            });
                            $('#customerForm')[0].reset();

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('Error') }}',
                                text: '{{ __('Error saving customer:') }} ' + response.message
                            });
                        }
                    },
                    error: function() {
                        // Handle AJAX request errors
                        alert('{{ __('An error occurred while saving the customer.') }}');
                    }
                });
            });
        });
    </script>

    <script>
        var currentEventId = null;
        var calendar = null;

        function updateStatusButtons(status, invoiceId) {
            $('.action-btn').addClass('d-none');

            if (status === 'requested') {
                $('#btnConfirm').removeClass('d-none');
                $('#btnCancel').removeClass('d-none');
            } else if (status === 'confirmed') {
                $('#btnCheckIn').removeClass('d-none');
                $('#btnCheckout').removeClass('d-none');
                $('#btnNoShow').removeClass('d-none');
                $('#btnCancel').removeClass('d-none');
            } else if (status === 'checked_in') {
                $('#btnStartService').removeClass('d-none');
                $('#btnCheckout').removeClass('d-none');
                $('#btnNoShow').removeClass('d-none');
                $('#btnCancel').removeClass('d-none');
            } else if (status === 'in_service') {
                $('#btnComplete').removeClass('d-none');
                $('#btnCheckout').removeClass('d-none');
                $('#btnCancel').removeClass('d-none');
            } else if (status === 'completed') {
                if (invoiceId) {
                    $('#btnViewInvoice').attr('href', `/admin/sales_invoices/invoice/${invoiceId}`).removeClass('d-none');
                }
            }
        }

        function proceedToCheckout() {
            if (!currentEventId) return;
            window.location.href = `{{ route('sales_invoices.create') }}?appointment_id=${currentEventId}`;
        }

        function triggerStatusAction(action) {
            if (!currentEventId) return;

            Swal.fire({
                title: '{{ __("Are you sure?") }}',
                text: '{{ __("Transition appointment status?") }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("Yes, proceed") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/appointments/${currentEventId}/${action}`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("Success") }}',
                                text: response.message || '{{ __("Status updated successfully") }}'
                            });
                            $('#eventModal').modal('hide');
                            calendar.refetchEvents();
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || '{{ __("Failed to update status.") }}';
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("Error") }}',
                                text: message
                            });
                        }
                    });
                }
            });
        }

        function openCancelPrompt() {
            if (!currentEventId) return;

            Swal.fire({
                title: '{{ __("Cancel Appointment") }}',
                input: 'textarea',
                inputLabel: '{{ __("Cancellation Reason") }}',
                inputPlaceholder: '{{ __("Please enter reason for cancellation...") }}',
                showCancelButton: true,
                confirmButtonText: '{{ __("Confirm Cancellation") }}',
                confirmButtonColor: '#dc3545',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return '{{ __("A cancellation reason is required!") }}';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    $.ajax({
                        url: `/admin/appointments/${currentEventId}/cancel`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            cancellation_reason: result.value
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("Cancelled") }}',
                                text: response.message || '{{ __("Appointment cancelled successfully") }}'
                            });
                            $('#eventModal').modal('hide');
                            calendar.refetchEvents();
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || '{{ __("Failed to cancel appointment.") }}';
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("Error") }}',
                                text: message
                            });
                        }
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                direction: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}',
                locale: '{{ app()->getLocale() }}',
                timeZone: 'UTC',
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: '{{ __("Today") }}',
                    month: '{{ __("Month") }}',
                    week: '{{ __("Week") }}',
                    day: '{{ __("Day") }}',
                    timeGridWeek: '{{ __("Week") }}',
                    timeGridDay: '{{ __("Day") }}'
                },
                events: '{{ route('appointments.index') }}',
                editable: false,
                eventClick: function(info) {
                    currentEventId = info.event.id;

                    var updateUrl = "{{ route('appointments.update', ':id') }}".replace(':id', info.event.id);
                    document.getElementById('appoentmentFormUpdate').action = updateUrl;

                    var destroyUrl = "{{ route('appointments.destroy', ':id') }}".replace(':id', info.event.id);
                    document.getElementById('appoentmentFormDelete').action = destroyUrl;

                    document.getElementById('id').value = info.event.id;
                    document.getElementById('id_destroy').value = info.event.id;

                    document.getElementById('edit_customer_id').value = info.event.extendedProps.customer_id;
                    document.getElementById('edit_provider_id').value = info.event.extendedProps.provider_id;
                    document.getElementById('edit_service_id').value = info.event.extendedProps.service_id;
                    document.getElementById('edit_start_date').value = info.event.extendedProps.start_date;
                    document.getElementById('edit_end_date').value = info.event.extendedProps.end_date;

                    var status = info.event.extendedProps.status || 'requested';
                    var rawStatusLabel = info.event.extendedProps.status_label || status;
                    var statusLabel = window.__ ? window.__(rawStatusLabel) : rawStatusLabel;
                    var statusBadge = info.event.extendedProps.status_badge || 'bg-secondary text-white';

                    $('#event_status_badge').text(statusLabel).attr('class', 'badge ' + statusBadge);

                    if (status === 'cancelled' && info.event.extendedProps.cancellation_reason) {
                        $('#event_cancelled_reason').text(info.event.extendedProps.cancellation_reason);
                        $('#event_cancellation_info').removeClass('d-none');
                    } else {
                        $('#event_cancellation_info').addClass('d-none');
                    }

                    updateStatusButtons(status, info.event.extendedProps.invoice_id);
                    openModal();
                }
            });

            calendar.render();

            function openModal() {
                $('#eventModal').modal('show');
            }

            function closeModal() {
                $('#eventModal').modal('hide');
            }

            window.onclick = function(event) {
                const modal = document.getElementById('eventModal');
                if (event.target == modal) {
                    closeModal();
                }
            };
        });
    </script>
@endsection
