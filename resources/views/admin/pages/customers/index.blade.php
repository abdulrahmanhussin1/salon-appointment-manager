@extends('admin.layouts.app')
@section('title')
    {{ __('Customers Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="customers">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Customers') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('customers.create'))
                <x-modal-button title="Customer" target="customerModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="customerModal" title="Create Customer">
        <form action="{{ route('customers.store') }}" method="POST" id="customerForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-2">
                        <x-form-select name='salutation' id="salutation" label="salutation" required>
                            <option @if (old('salutation') == 'Mr') selected @endif value="Mr">
                                {{ __('Mr') }}</option>
                            <option @if (old('salutation') == 'Ms') selected @endif value="Ms">
                                {{ __('Ms') }}</option>
                            <option @if (old('salutation') == 'Mrs') selected @endif value="Mrs">
                                {{ __('Mrs') }}</option>
                        </x-form-select>
                    </div>
                    <div class="col-8">
                        <x-input type='text' value="{{ old('name') }}" label="Name" name='name'
                            placeholder='Customer Name' id="name" oninput="" required />
                    </div>

                    <div class="col-2">
                        <div class="form-check mt-4 mb-0 pt-2">
                            <input class="form-check-input" type="checkbox" value="" name="is_vip" id="vip_checkbox">
                            <label class="form-check-label" for="vip_checkbox">
                                VIP
                            </label>
                        </div>
                    </div>
                </div>
                <x-input type="email" value="{{ old('email') }}" label="Email" name='email'
                    placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />
                <x-input type="text" value="{{ old('phone') }}" label="phone" id="phone" name='phone'
                    placeholder="phone  Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />


                    <div class="col-12">
                        <label class="form-label" for="dob">{{ __('Date Of Birth') }}</label>
                        <input type="date" name="dob" class="form-control  @error('dob') is-invalid @enderror"
                            id="dob" value="{{ isset($employee) ? $employee->dob : old('dob') }}"
                            required>
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
        <x-form-select name='status' id="status" label="status" required>
            <option @if (old('status') == 'active') selected @endif value="active">
                {{ __('Active') }}</option>
            <option @if (old('status') == 'inactive') selected @endif value="inactive">
                {{ __('Inactive') }}</option>
        </x-form-select>
    </div>
    <div class="col-6">
        <x-form-select name='gender' id="gender" label="gender" required>
            <option @if (old('gender') == 'male') selected @endif value="male">
                {{ __('Male') }}</option>
            <option @if (old('gender') == 'female') selected @endif value="female">
                {{ __('Female') }}</option>
        </x-form-select>
    </div>
</div>

            </div>
            <x-modal-footer />
        </form>


    </x-modal>
@endsection
@section('js')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
        $(document).ready(function() {
            $("#status,#salutation").select2({
                dropdownParent: $("#customerModal")
            });
            $(document).on('click', '.delete-this-customer', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this customer?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel!",
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-2', // Red button for confirmation
                        cancelButton: 'btn btn-secondary' // Gray button for cancel
                    },
                    buttonsStyling: false // Disable default SweetAlert styles and use Bootstrap 4
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            method: "DELETE",
                            url: url,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            success: function(msg) {
                                window.location.href =
                                    "{{ route('customers.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Customer is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
