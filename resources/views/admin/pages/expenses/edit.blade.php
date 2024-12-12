@extends('admin.layouts.app')
@section('title')
    {{ __('Edit Expense ') }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Expense">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('expenses.index') }}">{{ __('Expenses') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($expense) }}">
            {{ __('Edit :type', ['type' => $expense->name]) }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <div class="container ">
        <div class="card radius-15 border-lg-top-primary my-5">
            <div class="card-body">
                <div class="card-title">
                    <h4 class="mb-0">
                        {{ __('Edit :type', ['type' => $expense->name]) }}</h4>
                </div>
                <hr>
                <form method="POST" enctype="multipart/form-data" id="expenseForm"
                    action="{{ route('expenses.update', ['expense' => $expense]) }}">
                    @csrf @method('PUT')
                    <!-- Branch Selection -->
                    <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                        <option value="">{{ __('Select one Branch') }}</option>
                        @foreach ($branches as $branch)
                            <option @if ((isset($expense) && $expense->branch_id == $branch->id) || old('branch_id') == $branch->id) selected="selected" @endif
                                @if (!isset($expense) && Auth::user()->employee?->branch_id == $branch->id) selected="selected" @endif value="{{ $branch->id }}">
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form-select>

                    <!-- Expense Type Selection -->
                    <x-form-select name="expense_type_id" id="expense_type_id" label='Expense Type' required>
                        <option value="">{{ __('Select one Expense Type') }}</option>
                        @foreach ($expenseTypes as $expenseType)
                            <option @if ((isset($expense) && $expense->expense_type_id == $expenseType->id) || old('expense_type_id') == $expenseType->id) selected="selected" @endif
                                value="{{ $expenseType->id }}">
                                {{ $expenseType->name }}
                            </option>
                        @endforeach
                    </x-form-select>

                    <!-- Description -->
                    <x-form-description value="{{ old('description', isset($expense) ? $expense->description : '') }}"
                        label="Description" name='description' id='description' placeholder='Expense description' />

                    <div class="row">
                        <!-- Amount -->
                        <div class="col-6">
                            <x-input type='text' value="{{ old('amount', isset($expense) ? $expense->amount : '') }}"
                                label="Amount" name='amount' id='amount'  placeholder='Amount'
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>

                        <!-- Paid Amount -->
                        <div class="col-6">
                            <x-input type='text'
                                value="{{ old('paid_amount', isset($expense) ? $expense->paid_amount : '') }}"
                                label="Paid Amount" name='paid_amount'  id='paid_amount' placeholder='Paid Amount'
                                oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required />
                        </div>

                        <!-- Paid At -->
                        <div class="col-6">
                            <x-input type="date"
                                value="{{ old('paid_at', isset($expense) ? $expense->paid_at->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                label="Paid At" name='paid_at' id='paid_at' placeholder="Paid At" />
                        </div>

                        <!-- Invoice Number -->
                        <div class="col-6">
                            <x-input type='text'
                                value="{{ old('invoice_number', isset($expense) ? $expense->invoice_number : '') }}"
                                label="Invoice Number" name='invoice_number' id='invoice_number' placeholder="Invoice Number" />
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <x-form-select name="payment_method_id" id="payment_method_id" label='Payment Method' required>
                        <option value="">{{ __('Select one Payment Method') }}</option>
                        @foreach ($paymentMethods as $paymentMethod)
                            <option @if ((isset($expense) && $expense->payment_method_id == $paymentMethod->id) || old('payment_method_id') == $paymentMethod->id) selected="selected" @endif
                                @if (!isset($expense) && Auth::user()->employee?->payment_method_id == $paymentMethod->id) selected="selected" @endif value="{{ $paymentMethod->id }}">
                                {{ $paymentMethod->name }}
                            </option>
                        @endforeach
                    </x-form-select>

                    <!-- Status -->
                    <x-form-select name='status' id="status" label="Status" required>
                        <option @if (isset($expense) && $expense->status == 'active') selected @endif value="active">
                            {{ __('Active') }}
                        </option>
                        <option @if (isset($expense) && $expense->status == 'inactive') selected @endif value="inactive">
                            {{ __('Inactive') }}
                        </option>
                    </x-form-select>
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

            $('#expenseForm').validate({
                rules: {
                    branch_id: {
                        required: true,
                    },
                    expense_type_id: {
                        required: true,

                    },
                    description: {
                        maxlength: 1000
                    },
                    amount: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    paid_at: {
                        required: true,
                        date: true
                    },
                    invoice_number: {
                        maxlength: 20
                    },
                    paid_amount: {
                        required: true,
                        number: true,
                        min: 0
                    },

                    payment_method_id: {
                        required: true,

                    },
                    status: {
                        required: true,
                        in: ["active", "inactive"]
                    },

                },
                messages: {
                    branch_id: {
                        required: "Branch is required.",
                    },
                    expense_type_id: {
                        required: "Expense type is required.",
                    },
                    description: {
                        maxlength: "Description cannot exceed 1000 characters."
                    },
                    amount: {
                        required: "Amount is required.",
                        number: "Amount must be a valid number.",
                        min: "Amount must be greater than or equal to 0."
                    },
                    paid_at: {
                        required: "Paid date is required.",
                        date: "Paid date must be a valid date."
                    },
                    invoice_number: {
                        maxlength: "Invoice number cannot exceed 20 characters."
                    },
                    paid_amount: {
                        required: "Paid amount is required.",
                        number: "Paid amount must be a valid number.",
                        min: "Paid amount must be greater than or equal to 0."
                    },

                    payment_method_id: {
                        required: "Payment method is required.",
                    },
                    status: {
                        required: "Status is required.",
                        in: "Status must be active or inactive."
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
