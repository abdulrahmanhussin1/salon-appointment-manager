@extends('admin.layouts.app')
@section('title')
    {{ isset($employee) ? __('Edit Employee ') : __('Create Employee') }}
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
    <x-breadcrumb pageName="Employee">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('employees.index') }}">{{ __('Employees') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($employee) }}">
            {{ isset($employee) ? __('Edit :type', ['type' => $employee->name]) : __('Create New Employee') }}
        </x-breadcrumb-item>


    </x-breadcrumb>
    {{-- End breadcrumbs --}}







    <div class="container">
        @include('admin.layouts.alerts')
        <div class="card">
            <div class="card-title">
                <h4 class="m-3 mb-0">
                    {{ isset($employee) ? __('Edit :type', ['type' => $employee->name]) : __('Create New Employee') }}
                </h4>
            </div>
            <hr>

            <div class="card-body">
                <form method="POST"
                    action="{{ isset($employee) ? route('employees.update', ['employee' => $employee]) : route('employees.store') }}"
                    enctype="multipart/form-data" id="employeeForm">
                    @csrf
                    @if (isset($employee))
                        @method('PUT')
                    @endif
                    <!-- Default Accordion -->
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button " type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    {{ __('Employee Data') }}
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                data-bs-parent="#accordionExample" style="">
                                <div class="accordion-body">
                                    <div class="col-lg-12">
                                        <x-form-personal-image :src="isset($employee) && isset($employee->photo)
                                            ? asset('storage/' . $employee->photo)
                                            : asset('admin-assets/assets/img/avatar.jpg')" name="photo" />
                                    </div>

                                    <div class="row">


                                        <div class="col-12">
                                            <x-form-select name="employee_level_id" id="employee_level_id"
                                                label='Employee Level' required>
                                                <option value="">{{ __('Select one Employe Level') }}</option>
                                                @foreach ($employeeLevels as $employeeLevel)
                                                    <option @if (isset($employee) &&
                                                            ($employee->employee_level_id == $employeeLevel->id || old('employee_level_id') == $employeeLevel->id)) selected="selected" @endif
                                                        value="{{ $employeeLevel->id }}">{{ $employeeLevel->name }}
                                                    </option>
                                                @endforeach
                                            </x-form-select>
                                        </div>
                                        <div class="col-6">
                                            <x-input type='text' :value="isset($employee) ? $employee->name : old('name')" label="Name" name='name'
                                                placeholder='employee Name' id="name" oninput="" required />
                                        </div>

                                        <div class="col-6">
                                            <x-input type="text" value="{{ $employee->phone ?? old('phone') }}"
                                                label="Phone" id="phone" name='phone' placeholder="Phone"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" required />
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label" for="hiring_date">{{ __('Hiring Date') }}</label>
                                            <input type="date" name="hiring_date"
                                                class="form-control  @error('hiring_date') is-invalid @enderror"
                                                id="hiring_date"
                                                value="{{ isset($employee) ? $employee->hiring_date : old('hiring_date', date('Y-m-d')) }}"
                                                required>
                                            @error('hiring_date')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label" for="dob">{{ __('Date Of birth') }}</label>
                                            <input type="date" name="dob"
                                                class="form-control @error('dob') is-invalid @enderror" id="dob"
                                                value="{{ isset($employee) ? $employee->dob : old('dob', date('Y-m-d')) }}">
                                            @error('dob')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="col-6">
                                            <x-input type='email' :value="isset($employee) ? $employee->email : old('email')" label="email" name='email'
                                                placeholder='employee Email' id="email" oninput="" />
                                        </div>
                                        <div class="col-6">
                                            <x-input type='text' :value="isset($employee) ? $employee->job_title : old('job_title')" label="Job Title" name='job_title'
                                                placeholder='employee Job title' id="job_title" oninput="" />
                                        </div>

                                        <div class="col-6">
                                            <x-input type='text' :value="isset($employee)
                                                ? $employee->finger_print_code
                                                : old('finger_print_code')" label="Finger Print code "
                                                name='finger_print_code' placeholder='employee Finger Print code '
                                                id="finger_print_code" oninput="" />
                                        </div>





                                        <div class="col-6">
                                            <x-form-select name='gender' id="gender" label="gender" required>
                                                <option @if (isset($employee) && $employee->gender == 'male') selected @endif value="male">
                                                    {{ __('Male') }}</option>
                                                <option @if (isset($employee) && $employee->gender == 'female') selected @endif value="female">
                                                    {{ __('Female') }}</option>
                                            </x-form-select>
                                        </div>


                                        <div class="col-6">
                                            <x-input type="text"
                                                value="{{ $employee->national_id ?? old('national_id') }}"
                                                label="National ID" id="national_id" name='national_id'
                                                placeholder="National ID"
                                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                                        </div>

                                        <div class="col-6">
                                            <label for="idCardInput"
                                                class="form-label">{{ __('National Id Card') }}</label>
                                            <input class="form-control @error('id_card') is-invalid @enderror"
                                                type="file" name="id_card" id="idCardInput">
                                            @error('id_card')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="col-6">
                                            <x-form-select name='status' id="status" label="status" required>
                                                <option @if (isset($employee) && $employee->status == 'active') selected @endif value="active">
                                                    {{ __('Active') }}</option>
                                                <option @if (isset($employee) && $employee->status == 'inactive') selected @endif value="inactive">
                                                    {{ __('Inactive') }}</option>
                                            </x-form-select>
                                        </div>

                                        <div class="col-6">
                                            <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                                                <option value="">{{ __('Select one Branch') }}</option>
                                                @foreach ($branches as $branch)
                                                    <option @if (isset($employee) && ($employee->branch_id == $branch->id || old('branch_id') == $branch->id)) selected="selected" @endif
                                                        @if (!isset($invoice) && Auth::user()->employee?->branch_id == $branch->id) selected @endif
                                                        value="{{ $branch->id }}">{{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </x-form-select>
                                        </div>

                                        <div class="col-6">
                                            <x-input type='text' :value="isset($employee)
                                                ? $employee->inactive_reason
                                                : old('inactive_reason')" label="Inactive Reason"
                                                name='inactive_reason' placeholder='employee Inactive Reason'
                                                id="inactive_reason" oninput="" />
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label"
                                                for="termination_date">{{ __('Termination Date') }}</label>
                                            <input type="date" name="termination_date"
                                                class="form-control @error('termination_date')  is-invalid @enderror"
                                                id="termination_date"
                                                value="{{ isset($employee) ? $employee->termination_date : old('termination_date', date('Y-m-d')) }}"
                                                required>
                                            @error('termination_date')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <x-form-description value="{{ $employee->address ?? old('address') }}"
                                                label="address" name='address' placeholder='Employee address' />

                                        </div>
                                        <div class="col-6">
                                            <x-form-description value="{{ $employee->notes ?? old('notes') }}"
                                                label="notes" name='notes' placeholder='Employee notes' />

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    {{ __('Employee Wage') }}
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <x-form-select name='salary_type' id="salary_type" label="salary type">
                                                <option @if (isset($employeeWage) && $employeeWage->salary_type == 'monthly') selected @endif value="monthly">
                                                    {{ __('Monthly') }}</option>
                                                <option @if (isset($employeeWage) && $employeeWage->salary_type == 'weekly') selected @endif value="weekly">
                                                    {{ __('Weekly') }}</option>
                                                <option @if (isset($employeeWage) && $employeeWage->salary_type == 'daily') selected @endif value="daily">
                                                    {{ __('Daily') }}</option>
                                                <option @if (isset($employeeWage) && $employeeWage->salary_type == 'commission') selected @endif
                                                    value="commission">{{ __('Commission') }}</option>
                                            </x-form-select>
                                        </div>

                                        <div class="col-6">
                                            <x-form-select name='sales_target_settings' id="sales_target_settings"
                                                label="target settings">
                                                <option value="">{{ __('Select one Tareget Setting') }}</option>
                                                <option @if (isset($employeeWage) && $employeeWage->sales_target_settings == 'no') selected @endif value="no">
                                                    {{ __('No') }}</option>
                                                <option @if (isset($employeeWage) && $employeeWage->sales_target_settings == 'total_sales') selected @endif
                                                    value="total_sales">
                                                    {{ __('Total sales') }}</option>
                                                <option @if (isset($employeeWage) && $employeeWage->sales_target_settings == 'employee_daily_service') selected @endif
                                                    value="employee_daily_service">
                                                    {{ __('employee daily service') }}</option>
                                            </x-form-select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">

                                        <div class="col-6">
                                            <x-input type="text"
                                                value="{{ $employeeWage->basic_salary ?? old('basic_salary') }}"
                                                label="Basic Salary" id="basic_salary" name='basic_salary'
                                                placeholder="Basic Salary"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>

                                        <div class="col-6">
                                            <x-input type="text"
                                                value="{{ $employeeWage->bonus_salary ?? old('bonus_salary') }}"
                                                label="Bouns Salary" id="bonus_salary" name='bonus_salary'
                                                placeholder="Bouns Salary"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>

                                        <div class="col-4">
                                            <x-input type="text"
                                                value="{{ $employeeWage->allowance1 ?? old('allowance1') }}"
                                                label="Allowance1" id="allowance1" name='allowance1'
                                                placeholder="Allowance1"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>
                                        <div class="col-4">
                                            <x-input type="text"
                                                value="{{ $employeeWage->allowance2 ?? old('allowance2') }}"
                                                label="Allowance2" id="allowance2" name='allowance2'
                                                placeholder="Allowance2"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>

                                        <div class="col-4">
                                            <x-input type="text"
                                                value="{{ $employeeWage->allowance3 ?? old('allowance3') }}"
                                                label="Allowance3" id="allowance3" name='allowance3'
                                                placeholder="Allowance3"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>

                                        <div class="col-12 my-3">
                                            <label class="form-label" for="total_salary">{{ __('Total Salary') }}</label>
                                            <input type="text" id="total_salary" name="total_salary"
                                                class="form-control form-control-lg text-danger text-center"
                                                style="background-color: lightgray" value="0.00" readonly>
                                        </div>

                                        <hr>

                                        <div class="col-4">
                                            <x-input type="text"
                                                value="{{ $employeeWage->working_hours ?? old('working_hours') }}"
                                                label="working hours" id="working_hours" name='working_hours'
                                                placeholder="working hours"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>
                                        <div class="col-4">
                                            <label for="inputTime"
                                                class="form-label">{{ __('Start Working Time') }}</label>
                                            <input type="time"
                                                class="form-control @error('start_working_time') is-invalid @enderror"
                                                name="start_working_time" id="inputTime"
                                                value="{{ $employeeWage->start_working_time ?? old('start_working_time') }}">

                                            @error('start_working_time')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="col-4">
                                            <x-input type="text"
                                                value="{{ $employeeWage->overtime_rate ?? old('overtime_rate') }}"
                                                label="overtime rate" id="overtime_rate" name='overtime_rate'
                                                placeholder="overtime rate"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>


                                        <div class="col-6">
                                            <x-input type="text"
                                                value="{{ $employeeWage->penalty_late_hour ?? old('penalty_late_hour') }}"
                                                label="penalty late hour" id="penalty_late_hour" name='penalty_late_hour'
                                                placeholder="penalty late hour"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>


                                        <div class="col-6">
                                            <x-input type="text"
                                                value="{{ $employeeWage->penalty_absence_day ?? old('penalty_absence_day') }}"
                                                label="penalty absence day" id="penalty_absence_day"
                                                name='penalty_absence_day' placeholder="penalty absence day"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>


                                        <div class="col-6">
                                            <label for="inputTime" class="form-label">{{ __('Break Time') }}</label>
                                            <input type="time"
                                                class="form-control  @error('break_time') is-invalid @enderror"
                                                name="break_time" id="inputTime"
                                                value="{{ $employeeWage->break_time ?? old('break_time') }}">
                                            @error('break_time')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="col-6">
                                            <x-input type="text"
                                                value="{{ $employeeWage->break_duration_minutes ?? old('break_duration_minutes') }}"
                                                label="Break duration (in minutes)" id="break_duration_minutes"
                                                name='break_duration_minutes' placeholder="Break duration (in minutes)"
                                                oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')" />
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    {{ __('Assign Employee to Service') }}
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body">

                                    <div class="row">
                                        <div class="col-12">
                                            <x-form-multi-select label="Services" name="service_id[]" id="service_id"
                                                multiple>
                                                @foreach ($services as $service)
                                                    <option value="{{ $service->id }}"
                                                        @if (isset($employee) &&
                                                                ($employee->services->pluck('service_id')->contains($service->id) ||
                                                                    (old('service_id') && in_array($service->id, old('service_id'))))) selected @endif>
                                                        {{ $service->name }}
                                                    </option>
                                                @endforeach
                                            </x-form-multi-select>
                                        </div>
                                    </div>
                                    <hr>

                                    <!-- Placeholder for dynamically generated inputs -->
                                    <div id="service-details-container">
                                        @if (isset($employee) && $employee->services)
                                            @foreach ($employee->services as $service)
                                                <div class="service-details" data-service-id="{{ $service->id }}">
                                                    <h6>Commission for Service: {{ $service->name }}</h6>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label for="commission_type_{{ $service->id }}">Commission
                                                                Type</label>
                                                            <select name="commission_type[{{ $service->id }}]"
                                                                id="commission_type_{{ $service->id }}"
                                                                class="form-control">
                                                                <option value="percentage"
                                                                    {{ $service->pivot->commission_type === 'percentage' ? 'selected' : '' }}>
                                                                    Percentage</option>
                                                                <option value="value"
                                                                    {{ $service->pivot->commission_type === 'value' ? 'selected' : '' }}>
                                                                    Value</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="commission_value_{{ $service->id }}">Commission
                                                                Value</label>
                                                            <input type="number" min="0"
                                                                name="commission_value[{{ $service->id }}]"
                                                                id="commission_value_{{ $service->id }}"
                                                                class="form-control"
                                                                value="{{ $service->pivot->commission_value }}">
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label for="is_immediate_{{ $service->id }}">Immediate
                                                                Commission</label>
                                                            <select name="is_immediate_commission[{{ $service->id }}]"
                                                                id="is_immediate_{{ $service->id }}"
                                                                class="form-control">
                                                                <option value="1"
                                                                    {{ $service->pivot->is_immediate_commission ? 'selected' : '' }}>
                                                                    Yes</option>
                                                                <option value="0"
                                                                    {{ !$service->pivot->is_immediate_commission ? 'selected' : '' }}>
                                                                    No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                    </div>
                                    @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>




            </div><!-- End Default Accordion Example -->
            <div class="text-center mt-3">
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
            const servicesData = @json($services);

            // Listen to changes on the multi-select
            $('#service_id').on('change', function() {
                const selectedServices = $(this).val();
                const container = $('#service-details-container');
                container.empty(); // Clear existing inputs

                selectedServices.forEach(serviceId => {
                    const service = servicesData.find(s => s.id == serviceId);
                    if (!service) return;

                    // Generate inputs dynamically
                    const html = `
                    <div class="service-details" data-service-id="${serviceId}">
                        <h6>Commission for Service: ${service.name}</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="commission_type_${serviceId}">Commission Type</label>
                                <select name="commission_type[${serviceId}]" id="commission_type_${serviceId}" class="form-control">
                                    <option value="percentage">Percentage</option>
                                    <option value="value">Value</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="commission_value_${serviceId}">Commission Value</label>
                                <input type="number" name="commission_value[${serviceId}]" min="0" id="commission_value_${serviceId}" class="form-control">
                            </div>
                           <div class="col-md-4">
                                  <label for="is_immediate_${serviceId}">Immediate Commission</label>
                                <select name="is_immediate_commission[${serviceId}]" id="is_immediate_${serviceId}" class="form-control">
                                                                        <option value="0">No</option>

                                    <option value="1">Yes</option>
                                </select>
                            </div>
                                                    </div>

                        <hr>
                    </div>
                `;
                    container.append(html);
                });
            });

            // Trigger change event on page load for edit mode
            $('#service_id').trigger('change');
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
            // Function to calculate the total salary
            function calculateTotalSalary() {
                // Get values from the input fields, parsing them as numbers (or 0 if empty)
                let basicSalary = parseFloat($('#basic_salary').val()) || 0;
                let bonusSalary = parseFloat($('#bonus_salary').val()) || 0;
                let allowance1 = parseFloat($('#allowance1').val()) || 0;
                let allowance2 = parseFloat($('#allowance2').val()) || 0;
                let allowance3 = parseFloat($('#allowance3').val()) || 0;

                // Calculate the total
                let totalSalary = basicSalary + bonusSalary + allowance1 + allowance2 + allowance3;

                // Update the total salary input field
                $('#total_salary').val(totalSalary.toFixed(2)); // Rounds to 2 decimal places
            }

            // Attach event listener to update total salary on each input change
            $('#basic_salary, #bonus_salary, #allowance1, #allowance2, #allowance3').on('input', function() {
                calculateTotalSalary();
            });
            calculateTotalSalary();
        });
    </script>


    <script>
        $(document).ready(function() {
            $('#employeeForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 255
                    },
                    email: {
                        email: true,
                    },
                    phone: {
                        required: true,
                        maxlength: 15,
                    },
                    national_id: {
                        maxlength: 20,
                    },
                    address: {
                        maxlength: 500
                    },
                    notes: {
                        maxlength: 500
                    },
                    hiring_date: {
                        required: true,
                        date: true
                    },
                    dob: {
                        date: true,
                        max: new Date().toISOString().split("T")[0]
                    },
                    finger_print_code: {
                        maxlength: 50,
                    },
                    job_title: {
                        maxlength: 255
                    },

                    status: {
                        required: true,
                    },
                    employee_level_id: {
                        required: true,
                        digits: true
                    },
                    branch_id: {
                        required: true,
                        digits: true
                    },
                    salary_type: {
                        required: false,
                    },
                    basic_salary: {
                        number: true,
                        min: 0
                    },
                    bonus_salary: {
                        number: true,
                        min: 0
                    },
                    allowance1: {
                        number: true,
                        min: 0
                    },
                    allowance2: {
                        number: true,
                        min: 0
                    },
                    allowance3: {
                        number: true,
                        min: 0
                    },
                    total_salary: {
                        number: true,
                        min: 0
                    },
                    working_hours: {
                        number: true,
                        min: 0
                    },
                    overtime_rate: {
                        number: true,
                        min: 0
                    },
                    penalty_late_hour: {
                        number: true,
                        min: 0
                    },
                    penalty_absence_day: {
                        number: true,
                        min: 0
                    },
                    sales_target_settings: {
                        required: false,
                    },
                },
                messages: {
                    name: {
                        required: "The name is required.",
                        maxlength: "The name must not exceed 255 characters."
                    },
                    email: {
                        email: "Please enter a valid email address.",
                    },
                    phone: {
                        required: "The phone number is required.",
                        maxlength: "The phone number must not exceed 15 characters.",
                    },
                    national_id: {
                        maxlength: "The national ID must not exceed 20 characters.",
                    },
                    address: {
                        maxlength: "The address must not exceed 500 characters."
                    },
                    notes: {
                        maxlength: "Notes must not exceed 500 characters."
                    },
                    hiring_date: {
                        required: "Hiring date is required.",
                        date: "Please enter a valid date."
                    },
                    dob: {
                        date: "Please enter a valid date.",
                        max: "Date of birth must be before today."
                    },
                    finger_print_code: {
                        maxlength: "Fingerprint code must not exceed 50 characters.",
                    },
                    job_title: {
                        maxlength: "Job title must not exceed 255 characters."
                    },
                    status: {
                        required: "Status is required.",
                    },
                    employee_level_id: {
                        required: "Employee level is required.",
                        digits: "Please select a valid employee level ID."
                    },
                    branch_id: {
                        required: "Branch is required.",
                        digits: "Please select a valid branch ID."
                    },
                    basic_salary: {
                        number: "Please enter a valid number for Basic Salary.",
                        min: "Basic Salary cannot be less than 0."
                    },
                    bonus_salary: {
                        number: "Please enter a valid number for Bonus Salary.",
                        min: "Bonus Salary cannot be less than 0."
                    },
                    allowance1: {
                        number: "Please enter a valid number for Allowance 1.",
                        min: "Allowance 1 cannot be less than 0."
                    },
                    allowance2: {
                        number: "Please enter a valid number for Allowance 2.",
                        min: "Allowance 2 cannot be less than 0."
                    },
                    allowance3: {
                        number: "Please enter a valid number for Allowance 3.",
                        min: "Allowance 3 cannot be less than 0."
                    },
                    total_salary: {
                        number: "Please enter a valid number for Total Salary.",
                        min: "Total Salary cannot be less than 0."
                    },
                    working_hours: {
                        number: "Please enter a valid number for Working Hours.",
                        min: "Working Hours cannot be less than 0."
                    },
                    overtime_rate: {
                        number: "Please enter a valid number for Overtime Rate.",
                        min: "Overtime Rate cannot be less than 0."
                    },
                    penalty_late_hour: {
                        number: "Please enter a valid number for Penalty for Late Hour.",
                        min: "Penalty for Late Hour cannot be less than 0."
                    },
                    penalty_absence_day: {
                        number: "Please enter a valid number for Penalty for Absence Day.",
                        min: "Penalty for Absence Day cannot be less than 0."
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
                    form.submit();
                }
            });
        });
    </script>
@endsection
