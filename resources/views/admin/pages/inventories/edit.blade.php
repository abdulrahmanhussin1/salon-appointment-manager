@extends('admin.layouts.app')
@section('title')
    {{  __('Edit Inventory ')  }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Inventory">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('inventories.index') }}">{{ __('Inventories') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($inventory) }}">
            {{  __('Edit :type', ['type' => $inventory->name])  }}
        </x-breadcrumb-item>
    </x-breadcrumb>
{{-- End breadcrumbs --}}

<div class="container ">
    <div class="card radius-15 border-lg-top-primary my-5">
        <div class="card-body">
            <div class="card-title">
                <h4 class="mb-0">
                    {{  __('Edit :type', ['type' => $inventory->name])  }}</h4>
            </div>
            <hr>

            @include('admin.layouts.alerts')
            <form method="POST" id="inventoryForm" enctype="multipart/form-data" id="employee_levelForm"
                action="{{ route('inventories.update', ['inventory' => $inventory]) }}">
                @csrf @method('PUT')

                <div class="card-body">
                    <x-input type='text' value="{{ $inventory->name ?? old('name') }}" label="Name" name='name'
                    placeholder='Inventory Name' id="name" oninput="" required />
                     <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                    <option value="">{{ __('Select one Branch') }}</option>
                    @foreach ($branches as $branch)
                        <option @if (isset($inventory) && ($inventory->branch_id == $branch->id || old('branch_id') == $branch->id)) selected="selected" @endif
                            @if (!isset($invoice) && Auth::user()->employee?->branch_id == $branch->id) selected="selected" @endif value="{{ $branch->id }}">
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </x-form-select>
                <x-form-description value="{{ $inventory->description ?? old('description') }}" label="description" name='description'
                    placeholder='Inventory description' />
                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($inventory) && $inventory->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($inventory) && $inventory->status == 'inactive') selected @endif value="inactive">
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
       $('#inventoryForm').validate({
           rules: {
               name: {
                   required: true,
                   maxlength: 150
               },
               description: {
                    minlength: 3,
                    maxlength: 500
                },
                branch_id: {
                    required: true
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
               branch_id: {
                required: 'Please select a branch.'
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
