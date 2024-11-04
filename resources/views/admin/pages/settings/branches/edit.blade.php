@extends('admin.layouts.app')
@section('title')
    {{ __('Edit Branch ') }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="branches">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('branches.index') }}">{{ __('Branches') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($branch) }}">
            {{ __('Edit :type', ['type' => $branch->name]) }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <div class="container ">
        <div class="card radius-15 border-lg-top-primary my-5">
            <div class="card-body">
                <div class="card-title">
                    <h4 class="mb-0">
                        {{ __('Edit :type', ['type' => $branch->name]) }}</h4>
                </div>
                <hr>
                <form method="POST" id="branchForm" enctype="multipart/form-data" id="branchForm"
                    action="{{ route('branches.update', ['branch' => $branch]) }}">
                    @csrf @method('PUT')

                    <div class="card-body">
                        <x-input type="text" value="{{ isset($branch) ? $branch->name : old('name') }}" label="Name"
                            name='name' placeholder='Branch Name' id="branch_name" oninput="{{ null }}"
                            required />

                        <x-input type="text" value="{{ isset($branch) ? $branch->phone : old('phone') }}" label="phone"
                            id="phone" name='phone' placeholder="phone  Ex: 010xxxxxxxxx"
                            oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />

                        <x-input type="email" value="{{ isset($branch) ? $branch->email : old('email') }}" label="Email"
                            name='email' placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />


                        <x-form-description value="{{ isset($branch) ? $branch->address : old('address') }}"
                            label="address" name='address' placeholder='Branch address' />

                        <x-form-select name='manager_id' id="manager_id" label="manager">
                            <option value="">{{ __('Select one Manager') }}</option>
                            @foreach ($managers as $manager)
                                <option @if (isset($branch) && ($branch->manager_id == $manager->id || old('manager_id') == $manager->id)) selected="selected" @endif
                                    value="{{ $manager->id }}">
                                    {{ $manager->name }}</option>
                            @endforeach
                        </x-form-select>


                        <x-form-select name='status' id="status" label="status" required>
                            <option @if (isset($branch) && $branch->status == 'active') selected @endif value="active">
                                {{ __('Active') }}</option>
                            <option @if (isset($branch) && $branch->status == 'inactive') selected @endif value="inactive">
                                {{ __('Inactive') }}</option>
                        </x-form-select>
                        <x-file-input name='image' id="image" label="Image" />
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
            $('#branchForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 255
                    },
                    address: {
                        maxlength: 255 // Optional rule for address length, adjust if needed
                    },
                    phone: {
                        maxlength: 20,
                    },
                    email: {
                        email: true,
                        maxlength: 255,
                    },
                    status: {
                        required: true,
                    },
                    manager_id: {
                        digits: true,
                    }
                },
                messages: {
                    name: {
                        required: "The name field is required.",
                        maxlength: "The name may not be greater than 255 characters."
                    },
                    address: {
                        maxlength: "The address may not be greater than 255 characters."
                    },
                    phone: {
                        maxlength: "The phone may not be greater than 20 characters.",
                    },
                    email: {
                        email: "Please enter a valid email address.",
                        maxlength: "The email may not be greater than 255 characters.",
                    },
                    status: {
                        required: "The status field is required.",
                    },
                    manager_id: {
                        digits: "Manager ID must be an integer.",
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
