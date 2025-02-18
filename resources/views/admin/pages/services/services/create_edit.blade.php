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
        <x-breadcrumb-item active="{{ isset($service) }}">
            {{ isset($service) ? __('Edit :type', ['type' => $service->name]) : __('Create New Service') }}
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

                        <div class="col-4">
                            <x-input type="text" value="{{ $service->price ?? old('price') }}" label="price"
                                id="price" name='price' placeholder="price"
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>

                        <div class="col-2">
                            <div class="form-check form-switch mt-4 mb-0 pt-2">
                                <!-- Hidden input to handle unchecked state -->
                                <input type="hidden" name="is_target" value="0">
                                <!-- Checkbox input -->
                                <input class="form-check-input" type="checkbox" role="switch" value="1"
                                    name="is_target" id="flexSwitchCheckDefault"
                                    {{ (isset($service) && $service->is_target) || old('is_target') ? 'checked' : '' }}>
                                <label class="form-check-label" for="flexSwitchCheckDefault">{{ __('Target?') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-3">
                            <x-input type="text" value="{{ $service->outsie_price ?? old('outsie_price') }}"
                                label="Outside price" id="outsie_price" name='outsie_price' placeholder="Outside price"
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>
                        <div class="col-3">
                            <x-input type="text" value="{{ $service->duration ?? old('duration') }}"
                                label="duration (in minutes)" id="duration" name='duration'
                                placeholder="Duration in minustes"
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>

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
                            <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                                <option value="">{{ __('Select one Branch') }}</option>
                                @foreach ($branches as $branch)
                                    <option @if (isset($service) && ($service->branch_id == $branch->id || old('branch_id') == $branch->id)) selected="selected" @endif
                                        @if (!isset($invoice) && Auth::user()->employee?->branch_id == $branch->id) selected @endif value="{{ $branch->id }}">
                                        {{ $branch->name }}
                                    </option>
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
                            <x-form-multi-select label="Tools" name="tool_id[]" id="tool_id" multiple>
                                @foreach ($tools as $tool)
                                    <option value="{{ $tool->id }}" @if (isset($service) &&
                                            ($service->tools->pluck('id')->contains($tool->id) || (old('tool_id') && in_array($tool->id, old('tool_id'))))) selected @endif>
                                        {{ $tool->name }}
                                    </option>
                                @endforeach
                            </x-form-multi-select>
                        </div>




<div class="col-12">
    <x-form-multi-select label="Products" name="product_id[]" id="product_id" multiple>
        @foreach ($products as $product)
            <option value="{{ $product->id }}"
                @if (isset($service) &&
                    ($service->products->pluck('id')->contains($product->id) ||
                     (old('product_id') && in_array($product->id, old('product_id')))))
                    selected
                @endif>
                {{ $product->name }}
            </option>
        @endforeach
    </x-form-multi-select>
</div>


<div class="col-12">
    <x-form-multi-select label="Employees" name="employee_id[]" id="employee_id" multiple>
        @foreach ($employees as $employee)
            <option value="{{ $employee->id }}"
                @if (isset($service) &&
                    ($service->employees->pluck('id')->contains($employee->id) ||
                     (old('employee_id') && in_array($employee->id, old('employee_id')))))
                    selected
                @endif>
                {{ $employee->name }}
            </option>
        @endforeach
    </x-form-multi-select>
</div>


                        <div class="col-12">
                            <x-form-description value="{{ $service->notes ?? old('notes') }}" label="notes"
                                name='notes' placeholder='service notes' />
                        </div>
                    </div>
                    <div class="row">

<!-- Placeholder for dynamically generated inputs -->
<div id="employee-details-container">

</div>


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
    $(document).ready(function () {
        const employeesData = @json($employees);
        const preloadedEmployees = @json($service->employees ?? []); // Preloaded employees from the server
        const container = $('#employee-details-container');

        function renderEmployeeDetails(employeeId, preloadedData = null) {
            const employee = employeesData.find(e => e.id == employeeId);
            if (!employee) return;

            // Use preloaded data if available, otherwise default values
            const commissionType = preloadedData?.pivot?.commission_type || '';
            const commissionValue = preloadedData?.pivot?.commission_value || '';
            const isImmediate = preloadedData?.pivot?.is_immediate_commission || '';

            // Generate inputs dynamically
            const html = `
            <div class="employee-details" data-employee-id="${employeeId}">
                <h6>Commission for Employee: ${employee.name}</h6>
                <div class="row">
                    <div class="col-md-4">
                        <label for="commission_type_${employeeId}">Commission Type</label>
                        <select name="commission_type[${employeeId}]" id="commission_type_${employeeId}" class="form-control">
                            <option value="percentage" ${commissionType === 'percentage' ? 'selected' : ''}>Percentage</option>
                            <option value="value" ${commissionType === 'value' ? 'selected' : ''}>Value</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="commission_value_${employeeId}">Commission Value</label>
                        <input type="number" name="commission_value[${employeeId}]" id="commission_value_${employeeId}" class="form-control" value="${commissionValue}">
                    </div>

                    <div class="col-md-4">
                        <label for="is_immediate_${employeeId}">Immediate Commission</label>
                        <select name="is_immediate_commission[${employeeId}]" id="is_immediate_${employeeId}" class="form-control">
                            <option value="1" ${isImmediate == 1 ? 'selected' : ''}>Yes</option>
                            <option value="0" ${isImmediate == 0 ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                </div>
                <hr>
            </div>
        `;
            container.append(html);
        }

        // Populate preloaded employees
        preloadedEmployees.forEach(employee => {
            renderEmployeeDetails(employee.id, employee);
        });

        // Listen to changes on the multi-select
        $('#employee_id').on('change', function () {
            const selectedEmployees = $(this).val() || [];
            const existingEmployeeIds = container.find('.employee-details').map(function () {
                return $(this).data('employee-id');
            }).get();

            // Remove unselected employees
            container.find('.employee-details').each(function () {
                const employeeId = $(this).data('employee-id');
                if (!selectedEmployees.includes(employeeId.toString())) {
                    $(this).remove();
                }
            });

            // Add newly selected employees
            selectedEmployees.forEach(employeeId => {
                if (!existingEmployeeIds.includes(parseInt(employeeId))) {
                    renderEmployeeDetails(employeeId);
                }
            });
        });

        // Trigger change event on page load for edit mode
        $('#employee_id').trigger('change');
    });
</script>

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
        $(document).ready(function() {
            $('#serviceForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 100,
                    },
                    notes: {
                        maxlength: 500
                    },
                    price: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    outside_price: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    branch_id: {
                        required: true
                    },
                    service_category_id: {
                        required: true
                    },
                    "tool_id[]": {
                        required: false,
                        minlength: 1 // Adjust as needed
                    },
                    "product_id[]": {
                        required: false,
                        minlength: 1
                    },
                    "employee_id[]": {
                        required: false,
                        minlength: 1
                    },
                },
                messages: {
                    name: {
                        required: "The name is required.",
                        maxlength: "The name cannot exceed 100 characters.",
                    },
                    price: {
                        required: "The price is required.",
                        number: "Please enter a valid number.",
                        min: "The price cannot be less than zero."
                    },
                    outside_price: {
                        required: "The outside price is required.",
                        number: "Please enter a valid number.",
                        min: "The outside price cannot be less than zero."
                    },
                    branch_id: {
                        required: "Please select a branch."
                    },
                    service_category_id: {
                        required: "Please select a category."
                    },
                    "tool_id[]": {
                        minlength: "Please select at least one tool."
                    },
                    "product_id[]": {
                        minlength: "Please select at least one product."
                    },
                    "employee_id[]": {
                        minlength: "Please select at least one employee."
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
                submitHandler: function(form) {
                    // Custom validation logic can go here
                    let hasErrors = false;

                    // Example of a custom check (optional)
                    // Replace this section if specific checks are needed.
                    // if (customConditionFails) {
                    //     hasErrors = true;
                    //     alert('Custom validation error message.');
                    // }

                    if (!hasErrors) {
                        form.submit(); // Submit the form if there are no custom errors
                    } else {
                        alert('Please fix the errors in the form before submitting.');
                    }
                }
            });

        });
    </script>
@endsection
