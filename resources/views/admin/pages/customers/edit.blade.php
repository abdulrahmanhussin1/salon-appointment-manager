@extends('admin.layouts.app')
@section('title')
    {{ __('Edit Customer ') }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Customer">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('customers.index') }}">{{ __('customers') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($customer) }}">
            {{ __('Edit :type', ['type' => $customer->name]) }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <div class="container ">
        <div class="card radius-15 border-lg-top-primary my-5">
            <div class="card-body">
                <div class="card-title">
                    <h4 class="mb-0">
                        {{ __('Edit :type', ['type' => $customer->name]) }}</h4>
                </div>
                <hr>
<form method="POST" id="customerForm" enctype="multipart/form-data" action="{{ route('customers.update', ['customer' => $customer]) }}">
    @csrf
    @method('PUT')

    <div class="card-body">
        <div class="row">
            <div class="col-2">
                <x-form-select name='salutation' id="salutation" label="salutation">
                    <option @if (($customer && $customer->salutation == 'Mr') || old('salutation') == 'Mr') selected @endif value="Mr">{{ __('Mr') }}</option>
                    <option @if (($customer && $customer->salutation == 'Ms') || old('salutation') == 'Ms') selected @endif value="Ms">{{ __('Ms') }}</option>
                    <option @if (($customer && $customer->salutation == 'Mrs') || old('salutation') == 'Mrs') selected @endif value="Mrs">{{ __('Mrs') }}</option>
                    <option @if (($customer && $customer->salutation == 'Dr') || old('salutation') == 'Dr') selected @endif value="Dr">{{ __('Dr') }}</option>
                    <option @if (($customer && $customer->salutation == 'Eng') || old('salutation') == 'Eng') selected @endif value="Eng">{{ __('Eng') }}</option>
                </x-form-select>
            </div>
            <div class="col-8">
                <x-input type='text' value="{{ $customer ? $customer->name : old('name') }}" label="Name" name='name'
                    placeholder='Customer Name' id="name" required />
            </div>

            <div class="col-2">
                <div class="form-check form-switch mt-4 mb-0 pt-2">
                    <!-- Hidden input to handle unchecked state -->
                    <input type="hidden" name="is_vip" value="0">
                    <!-- Checkbox input -->
                    <input class="form-check-input" type="checkbox" role="switch" value="1" name="is_vip" id="flexSwitchCheckDefault"
                        {{ ($customer && $customer->is_vip) || old('is_vip') ? 'checked' : '' }}>
                    <label class="form-check-label" for="flexSwitchCheckDefault">{{ __('VIP') }}</label>
                </div>
            </div>
        </div>
        <x-input type="email" value="{{ $customer ? $customer->email : old('email') }}" label="Email" name='email'
            placeholder='Example@gmail.com' id="email" />
        <x-input type="text" value="{{ $customer ? $customer->phone : old('phone') }}" label="Phone" id="phone" name='phone'
            placeholder="phone Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required />

        <div class="col-12">
            <label class="form-label" for="dob">{{ __('Date Of Birth') }}</label>
            <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror"
                id="dob" value="{{ $customer ? $customer->dob : old('dob') }}">
            @error('dob')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <x-form-description value="{{ $customer ? $customer->address : old('address') }}" label="Address" name='address'
            placeholder='Customer Address' />
        <x-form-description value="{{ $customer ? $customer->notes : old('notes') }}" label="Notes" name='notes'
            placeholder='Notes' />

        <div class="row">
            <div class="col-6">
                <x-form-select name='status' id="status" label="Status" required>
                    <option @if (($customer && $customer->status == 'active') || old('status') == 'active') selected @endif value="active">{{ __('Active') }}</option>
                    <option @if (($customer && $customer->status == 'inactive') || old('status') == 'inactive') selected @endif value="inactive">{{ __('Inactive') }}</option>
                </x-form-select>
            </div>
            <div class="col-6">
                <x-form-select name='gender' id="gender" label="Gender" required>
                    <option @if (($customer && $customer->gender == 'male') || old('gender') == 'male') selected @endif value="male">{{ __('Male') }}</option>
                    <option @if (($customer && $customer->gender == 'female') || old('gender') == 'female') selected @endif value="female">{{ __('Female') }}</option>
                </x-form-select>
            </div>
            <div class="col-12">
                <x-form-select name='added_from' id="added_from" label="Added From">
                    <option @if (($customer && $customer->add_from == 'direct') || old('add_from') == 'direct') selected @endif value="direct">{{ __('Direct') }}</option>
                    <option @if (($customer && $customer->add_from == 'online') || old('add_from') == 'online') selected @endif value="online">{{ __('Online') }}</option>
                    <option @if (($customer && $customer->add_from == 'advertisement') || old('add_from') == 'advertisement') selected @endif value="advertisement">{{ __('Advertisement') }}</option>
                    <option @if (($customer && $customer->add_from == 'referral') || old('add_from') == 'referral') selected @endif value="referral">{{ __('Referral') }}</option>
                    <option @if (($customer && $customer->add_from == 'walk_in') || old('add_from') == 'walk_in') selected @endif value="walk_in">{{ __('Walk In') }}</option>
                </x-form-select>
            </div>
        </div>

    </div>
    <div class="text-center mt-2">
        <x-submit-button label='Confirm' />
    </div>
</form>

            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#supplierForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 255
                    },
                    salutation: {
                        required: true,
                    },
                    status: {
                        required: true,
                    },
                    gender: {
                        required: true,
                    },
                    added_from: {
                        required: true,
                    },
                    dob: {
                        date: true
                    },
                    email: {
                        email: true,

                    },
                    phone: {
                        digits: true,
                        maxlength: 15,

                    },
                    address: {
                        maxlength: 255
                    },
                    is_vip: {
                        boolean: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter the customer's name.",
                        maxlength: "Name cannot be longer than 255 characters."
                    },
                    salutation: {
                        required: "Please select a salutation."
                    },
                    status: {
                        required: "Please select the status."
                    },
                    gender: {
                        required: "Please select the gender."
                    },
                    added_from: {
                        required: "Please select how the customer was added."
                    },
                    dob: {
                        date: "Please enter a valid date."
                    },
                    email: {
                        email: "Please enter a valid email address.",
                    },
                    phone: {
                        digits: "Please enter a valid phone number.",
                        maxlength: "Phone number cannot be longer than 15 digits.",
                    },
                    address: {
                        maxlength: "Address cannot be longer than 255 characters."
                    }
                },
                errorClass: "error text-danger fs--1",
                errorElement: "span",
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass(errorClass).removeClass(validClass);
                    $(element.form).find("label[for=" + element.id + "]").addClass(errorClass);
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass(errorClass).addClass(validClass);
                    $(element.form).find("label[for=" + element.id + "]").removeClass(errorClass);
                },
            });
        });
    </script>
@endsection
