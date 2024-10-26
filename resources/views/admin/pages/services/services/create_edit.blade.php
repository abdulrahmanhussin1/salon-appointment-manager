@extends('admin.layouts.app')
@section('title')
    {{ isset($Service) ? __('Edit Service ') : __('Create Service') }}
@endsection
@section('css')
    <link href="{{ asset('admin-assets/assets/vendor/choices/choices.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-assets/assets/vendor/choices/theme.min.css') }}" type="text/css" rel="stylesheet"
        id="style-default">
    <style>
        .custom-avatar {
            display: inline-block;
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
        }

        .custom-avatar img {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            transition: opacity 0.25s;
            display: block;
        }

        .custom-avatar .overlay {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .custom-avatar:hover img,
        .custom-avatar:hover .overlay {
            opacity: 1;
        }

        .custom-avatar .icon {
            color: #ffffff;
            font-size: 32px;
        }
    </style>
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Service">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('services.index') }}">{{ __('Services') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($role) }}">
            {{ isset($role) ? __('Edit :type', ['type' => $role->name]) : __('Create New Service') }}
        </x-breadcrumb-item>


    </x-breadcrumb>
    {{-- End breadcrumbs --}}
    <div class="container">
        <div class="card radius-15 border-lg-top-primary">
            <div class="card-title">
                <h4 class="m-3 mb-0">
                    {{ isset($service) ? __('Edit :type', ['type' => $service->name]) : __('Create New Service') }}
                </h4>
            </div>
            @include('admin.layouts.alerts')
            <hr>
            <form method="POST"
                action="{{ isset($service) ? route('services.update', ['service' => $service]) : route('services.store') }}"
                enctype="multipart/form-data" id="serviceForm">
                @csrf
                @if (isset($service))
                    @method('PUT')
                @endif
                <div class="card-body">
                    <div class="col-lg-12">
                        <x-form-personal-image :src="isset($service) && isset($service->photo)
                            ? asset('storage/' . $service->photo)
                            : asset('admin-assets/assets/img/OIP.jpeg')" name="image" />
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <x-input type='text' :value="isset($service) ? $service->name : old('name')" label="Name" name='name'
                                placeholder='service Name' id="name" oninput="" required />
                        </div>

                        <div class="col-6">
                            <x-input type="text" value="{{ $service->price ?? old('price') }}" label="price"
                                id="price" name='price' placeholder="price"
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-6">
                            <x-form-select name="service_category_id" id="service_category_id" label='Category' required>
                                <option value="">{{ __('Select one Category') }}</option>
                                @foreach ($serviceCategories as $category)
                                    <option @if (isset($service) && ($service->service_category_id == $category->id || old('service_category_id') == $category->id)) selected="selected" @endif
                                        value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-form-select>
                        </div>

                        <div class="col-6">
                            <x-form-select name='status' id="status" label="status" required>
                                <option @if (isset($service) && $service->status == 'active') selected @endif value="active">
                                    {{ __('Active') }}</option>
                                <option @if (isset($service) && $service->status == 'inactive') selected @endif value="inactive">
                                    {{ __('Inactive') }}</option>
                            </x-form-select>
                        </div>

                        <div class="col-12">
                            <x-form-multi-select label="Tools" name="tool_id" id="tool_id">
                                @foreach ($tools as $tool)
                                    <option @if (isset($service) && ($service->tool_id == $tool->id || old('tool_id') == $tool->id)) selected="selected" @endif
                                        value="{{ $tool->id }}">{{ $tool->name }}</option>
                                @endforeach
                            </x-form-multi-select>
                        </div>

                        <div class="col-12">
                            <x-form-multi-select label="Products" name="product_id" id="product_id">
                                @foreach ($products as $product)
                                    <option @if (isset($service) && ($service->product_id == $product->id || old('product_id') == $product->id)) selected="selected" @endif
                                        value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </x-form-multi-select>
                        </div>

                        <div class="col-12">
                            <x-form-multi-select label="Employees" name="employee_id" id="employee_id">
                                @foreach ($employees as $employee)
                                    <option @if (isset($service) && ($service->employee_id == $employee->id || old('employee_id') == $employee->id)) selected="selected" @endif
                                        value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </x-form-multi-select>
                        </div>


                        <div class="col-12">
                            <x-form-description value="{{ $service->notes ?? old('notes') }}" label="notes" name='notes'
                                placeholder='service notes' />
                        </div>
                    </div>
                    <div class="employee_commission_container d-none">
                        <hr>

                    </div>
                </div>
                <div class="text-center mt-2">
                    <x-submit-button label='Confirm' />

                </div>
            </form>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function openFileInput() {
            document.getElementById('fileInput').click();
        }

        function handleFileSelect() {
            const fileInput = document.getElementById('fileInput');
            const avatarImage = document.getElementById('avatar');

            const selectedFile = fileInput.files[0];

            if (selectedFile) {

                const reader = new FileReader();

                reader.onload = function(e) {
                    avatarImage.src = e.target.result;
                };

                reader.readAsDataURL(selectedFile);
            }
        }
    </script>

    <script>
        $(document).on('change', '#employee_id', function() {
            $('.employee_commission_container')
            .removeClass('d-none')
            .append(`
                <div class="row">
                                        <div class="col-4">
                        <label for="employee_commission">{{ __('Employee Commission') }}</label>
                        <input type="text" min="0" step="0.01" id="employee_commission" value="Employee Name" name="employee_commission" readonly="readonly"
                            placeholder="{{ __('Employee ') }}">
                    </div>
                    <div class="col-4">
                        <label for="employee_commission">{{ __('Employee Commission') }}</label>
                        <input type="number" min="0" step="0.01" id="employee_commission" name="employee_commission"
                            placeholder="{{ __('Employee Commission') }}">
                    </div>
                    <div class="col-4">
                        <label for="employee_commission_percentage">{{ __('Employee Commission Percentage') }}</label>
                        <input type="number" min="0" step="0.01" id="employee_commission_percentage"
                            name="employee_commission_percentage" placeholder="{{ __('Employee Commission Percentage') }}">
                            <small class="text-muted">{{ __('Format: 0.00') }}</small>
                       </div>
            `);

        });
    </script>




    <script>
        $(document).ready(function() {
            $('#productForm').validate({
                rules: {

                },
                messages: {

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
                submitHandler: function(form) {
                    form.submit();
                }
            });
        });
    </script>
@endsection
