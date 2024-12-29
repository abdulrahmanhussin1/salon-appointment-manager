@extends('admin.layouts.app')
@section('title')
    {{ __('Calender') }}
@endsection
@section('content')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <x-breadcrumb pageName="Home">
        <x-breadcrumb-item>{{ __('Home') }}</x-breadcrumb-item>
    </x-breadcrumb>
    <div class="ms-2 row">
        <div class="card-title col-8">Customer Details</div>
        <div class="col-4 text-end mt-3 pe-4">
            @if (App\Traits\AppHelper::perUSer('customers.create'))
                <x-modal-button title="Customer" target="customerModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
    </div>
    <div class="ms-2 row">
        <div class="card-title col-8">Appoentment Details</div>
        <div class="col-4 text-end mt-3 pe-4">
            <x-modal-button title="Appoentment" target="appoentmentModal"><i
                    class="bi bi-plus-lg me-2"></i></x-modal-button>
        </div>
    </div>




    <div id="calendar"></div>

    <!-- Modal -->

    <x-modal id="eventModal" title="Edit Appoentment">

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
                                    value="{{ $branch->id }}">
                                    {{ $branch->name }}
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
                    <label class="form-label" for="end_date">{{ __('End Date') }}</label>
                    <input type="datetime-local" name="end_date"
                        class="form-control w-100  @error('end_date') is-invalid @enderror" id="edit_end_date"
                        value="" required>
                    @error('end_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

            </div>
            <Button type="submit" class="btn btn-success">Update</Button>
        </form>

        <form class="text-center my-3"  action="{{ route('appointments.destroy', 1) }}" method="POST" id="appoentmentFormDelete"
            enctype="multipart/form-data">
            @csrf
            @method('DELETE')
            <input type="hidden" name="id" id="id_destroy">
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>

    </x-modal>


    <x-modal id="customerModal" title="Create Customer">
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
                        <x-form-select name='added_from' id="added_from" label="added from">
                            <option @if (old('added_from') == 'direct') selected @endif value="direct">
                                {{ __('Direct') }}</option>
                            <option @if (old('added_from') == 'online') selected @endif value="online">
                                {{ __('Online') }}</option>
                            <option @if (old('added_from') == 'advertisement') selected @endif value="advertisement">
                                {{ __('Advertisement') }}</option>
                            <option @if (old('added_from') == 'referral') selected @endif value="referral">
                                {{ __('Referral') }}</option>
                            <option @if (old('added_from') == 'walk_in') selected @endif value="walk_in">
                                {{ __('walk_in') }}</option>

                        </x-form-select>
                    </div>
                </div>

            </div>
            <x-modal-footer />
        </form>
    </x-modal>



    <x-modal id="appoentmentModal" title="Create Appointment ">
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
                                    value="{{ $branch->id }}">
                                    {{ $branch->name }}
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
                    <label class="form-label" for="end_date">{{ __('End Date') }}</label>
                    <input type="datetime-local" name="end_date"
                        class="form-control w-100  @error('end_date') is-invalid @enderror" id="end_date"
                        value="" required>
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Customer saved successfully!'
                            });
                            $('#customerForm')[0].reset();

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error saving customer: ' + response.message
                            });
                        }
                    },
                    error: function() {
                        // Handle AJAX request errors
                        alert('An error occurred while saving the customer.');
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                timeZone: 'UTC',
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay'
                },
                events: '{{ route('appointments.index') }}',
                editable: false, // Enables drag-and-drop editing
                eventClick: function(info) {
                    // Open a prompt to edit the event title
                    document.getElementById('id').value = info.event.id;
                    document.getElementById('id_destroy').value = info.event.id;


                    document.getElementById('edit_customer_id').value = info.event.extendedProps
                        .customer_id;
                    document.getElementById('edit_provider_id').value = info.event.extendedProps
                        .provider_id;
                    document.getElementById('edit_service_id').value = info.event.extendedProps
                        .service_id;
                    document.getElementById('edit_start_date').value = info.event.extendedProps
                        .start_date;
                    document.getElementById('edit_end_date').value = info.event.extendedProps.end_date;


                    console.log(info.event.extendedProps.start_date, info.event.customer_id,
                        info.event.id, info.event.extendedProps.customer);


                    // const saveButton = document.getElementById('saveEventButton');
                    // saveButton.onclick = function() {
                    //     const newTitle = document.getElementById('eventTitleInput').value;
                    //     if (newTitle) {
                    //         info.event.setProp('title', newTitle); // Update the event title
                    //     }
                    //     closeModal();
                    // };

                    openModal();
                }
            });

            calendar.render();

            function openModal() {
               // document.getElementById('eventModal').style.display = 'block';
                $('#eventModal').modal('show');

            }

            function closeModal() {
                $('#eventModal').modal('hide');

                //  document.getElementById('eventModal').style.display = 'none';
            }

            // Close modal on clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('eventModal');
                if (event.target == modal) {
                    closeModal();
                }
            };
        });
    </script>
@endsection
