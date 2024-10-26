@extends('admin.layouts.app')
@section('title')
    {{  __('Edit Service Category ')  }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Service Category">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('service_categories.index') }}">{{ __('Service Categories') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($serviceCategory) }}">
            {{  __('Edit :type', ['type' => $serviceCategory->name])  }}
        </x-breadcrumb-item>
    </x-breadcrumb>
{{-- End breadcrumbs --}}

<div class="container ">
    <div class="card radius-15 border-lg-top-primary my-5">
        <div class="card-body">
            <div class="card-title">
                <h4 class="mb-0">
                    {{  __('Edit :type', ['type' => $serviceCategory->name])  }}</h4>
            </div>
            <hr>
            <form method="POST" id="service_categoryForm" enctype="multipart/form-data" id="service_categoryForm"
                action="{{ route('service_categories.update', ['service_category' => $serviceCategory]) }}">
                @csrf @method('PUT')

                <div class="card-body">
                    <x-input type='text' value="{{ $serviceCategory->name ?? old('name') }}" label="Name" name='name'
                    placeholder='service Category Name' id="name" oninput="" required />
                <x-form-description value="{{ $serviceCategory->description ?? old('description') }}" label="description" name='description'
                    placeholder='Service Category description' />
                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($serviceCategory) && $serviceCategory->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($serviceCategory) && $serviceCategory->status == 'inactive') selected @endif value="inactive">
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
       $('#service_categoryForm').validate({
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
