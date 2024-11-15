@extends('admin.layouts.app')
@section('title')
    {{  __('Edit Supplier ')  }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Supplier">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('suppliers.index') }}">{{ __('Suppliers') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($supplier) }}">
            {{  __('Edit :type', ['type' => $supplier->name])  }}
        </x-breadcrumb-item>
    </x-breadcrumb>
{{-- End breadcrumbs --}}

<div class="container ">
    <div class="card radius-15 border-lg-top-primary my-5">
        <div class="card-body">
            <div class="card-title">
                <h4 class="mb-0">
                    {{  __('Edit :type', ['type' => $supplier->name])  }}</h4>
            </div>
            <hr>
            <form method="POST" id="supplierForm" enctype="multipart/form-data" id="supplierForm"
                action="{{ route('suppliers.update', ['supplier' => $supplier]) }}">
                @csrf @method('PUT')

                <div class="card-body">
                    <x-input type='text' value="{{ $supplier->name ?? old('name') }}" label="Name" name='name'
                    placeholder='supplier Name' id="name" oninput="" required />
                <x-input type="email" value="{{ $supplier->email ?? old('email') }}" label="Email" name='email'
                    placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />
                <x-input type="text" value="{{$supplier->phone ?? old('phone') }}" label="phone" id="phone" name='phone'
                    placeholder="phone  Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />

                    <x-input type="text" value="{{ $supplier->initial_balance ?? old('initial_balance') }}" label="initial balance" id="initial_balance" name='initial_balance'
                    placeholder="0.00 L.E" oninput="this.value = this.value.replace(/[^0-9+-]/g, '')" />
                <x-form-description value="{{ $supplier->address ?? old('address') }}" label="address" name='address'
                    placeholder='supplier Address' />
                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($supplier) && $supplier->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($supplier) && $supplier->status == 'inactive') selected @endif value="inactive">
                        {{ __('Inactive') }}</option>
                </x-form-select>
                    <div class="text-center mt-2">
                        <x-submit-button label='Confirm' />
                    </div>
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
                   maxlength: 150
               },
               description: {
                    minlength: 3,
                    maxlength: 500
                },
               status: {
                   required: true
               },
           },
           messages: {
               name: {
                   required: 'Please enter your name.',
                   maxlength: 'Name must not exceed 150 characters.'
               },
               description: {
                    minlength: 'Description must be at least 4 characters',
                    maxlength: 'Description must not exceed 500 characters.'
                },

               status: {
                   required: 'Please select a status.'
               },
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
