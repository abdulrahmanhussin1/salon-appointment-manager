@extends('admin.layouts.app')
@section('title')
    {{ isset($role) ? __('Edit Role ') : __('Create Role') }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Role">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('roles.index') }}">{{ __('Roles') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($role) }}">
            {{ isset($role) ? __('Edit :type', ['type' => $role->name]) : __('Create New Role') }}
        </x-breadcrumb-item>


    </x-breadcrumb>
{{-- End breadcrumbs --}}

    <div class="container ">
        <div class="card radius-15 border-lg-top-primary my-5">
            <div class="card-body">
                <div class="card-title">
                    <h4 class="mb-0">
                        {{ isset($role) ? __('Edit :type', ['type' => $role->name]) : __('Create New Role') }}</h4>
                </div>
                <hr>
                <form method="POST" id="roleForm"
                    action="{{ isset($role) ? route('roles.update', ['role' => $role]) : route('roles.store') }}">
                    @csrf
                    @if (isset($role))
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <x-input type="text" :value="isset($role) ? $role->name : old('name')" label="Name" name='name' placeholder='role Name'
                            id="role_name" oninput="{{ null }}" required />
                        <x-form-description value="{{ isset($role) ? $role->description : old('description') }}"
                            label="Description" name='description' placeholder='Role Description' />
                        <x-form-select name='status' id="status" label="status" required>
                            <option @if (isset($role) && $role->status == 'active') selected @endif value="active">
                                {{ __('Active') }}</option>
                            <option @if (isset($role) && $role->status == 'inactive') selected @endif value="inactive">
                                {{ __('Inactive') }}</option>
                        </x-form-select>

                        @include('admin.layouts.permissions_table')
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
    <script src="{{ asset('admin-assets/assets/js/permissions_table.js') }}"></script>

    <script>
        $(document).ready(function() {
           $('#roleForm').validate({
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
