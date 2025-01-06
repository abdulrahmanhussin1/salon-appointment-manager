@extends('admin.layouts.app')
@section('title')
    {{ __('Expenses Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Expenses">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Expenses') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}



    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('expenses.create'))
                <x-modal-button title="Expense" target="ExpensesModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>

        <form method="get" id="expenseForm" class="my-3 bg-white my-3 rounded-2 shadow px-5 py-2 pt-4" action="{{ route('expenses.index') }}">
            <div class="">
                <div class="row">

                    <div class="col-3">
                        <input type="date" @if ( request('start_date') ) value="{{request('start_date') }}" @endif  name="start_date" class="form-control me-2" placeholder="Start Date">
                    </div>
                    <div class="col-3">
                        <input type="date" @if ( request('end_date') ) value="{{request('end_date') }}" @endif  name="end_date" class="form-control me-2" placeholder="End Date">
                    </div>
                    <div class="col-3">
                        <x-form-select name="expense_type_id"   class=" me-2" >
                            <option value="">{{ __('Select one Expense Type') }}</option>
                            @foreach (App\Models\ExpenseType::all() as $expenseType)
                                <option @if ( !empty( request('expense_type_id') ) && request('expense_type_id') == $expenseType->id  ) selected="selected" @endif value="{{ $expenseType->id }}">
                                    {{ $expenseType->name }}
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>
                    <div class="col-3">
                        <x-form-select name="branch_id"   class=" me-2" >
                            <option value="">{{ __('Select one Branch') }}</option>
                            @foreach (App\Models\Branch::all() as $expenseType)
                                <option @if ( !empty( request('branch_id') ) && request('branch_id') == $expenseType->id  ) selected="selected" @endif value="{{ $expenseType->id }}">
                                    {{ $expenseType->name }}
                                </option>
                            @endforeach
                        </x-form-select>
                    </div>
                    <div class="col-3">
                        <button type="submit" id="filter" class="btn btn-primary">Filter</button>
                    </div>

                </div>



            </div>
        </form>


        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="ExpensesModal" title="Create Expense">
        <form action="{{ route('expenses.store') }}" method="POST" id="expensesForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">


                <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                    <option value="">{{ __('Select one Branch') }}</option>
                    @foreach ($branches as $branch)
                        <option @if (isset($product) && ($product->branch_id == $branch->id || old('branch_id') == $branch->id)) selected="selected" @endif
                            @if (!isset($invoice) && Auth::user()->employee?->branch_id == $branch->id) selected="selected" @endif value="{{ $branch->id }}">
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </x-form-select>

                <x-form-select name="expense_type_id" id="expense_type_id" label='expense Type' required>
                    <option value="">{{ __('Select one Expense Type') }}</option>
                    @foreach ($expenseTypes as $expenseType)
                        <option @if (isset($expense) && ($expense->expense_type_id == $expenseType->id || old('expense_type_id') == $expenseType->id)) selected="selected" @endif
                            value="{{ $expenseType->id }}">
                            {{ $expenseType->name }}
                        </option>
                    @endforeach
                </x-form-select>

                <x-form-description value="{{ old('description') }}" label="description" name='description'
                    placeholder='Expense description' />

                <div class="row">
                    <div class="col-6">
                        <x-input type='text' value="{{ old('amount') }}" label="Amount" name='amount'
                            placeholder='Amount' oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                    </div>
                    <div class="col-6">
                        <x-input type='text' value="{{ old('paid_amount') }}" label="paid amount" name='paid_amount'
                            placeholder='paid_amount' oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required />
                    </div>

                    <div class="col-6">
                        <x-input type='date' value="{{ old('paid_at') ?? date('Y-m-d') }}" label="paid_at"
                            name='paid_at' placeholder="paid_at" />
                    </div>
                    <div class="col-6">
                        <x-input type='text' value="{{ old('invoice _number') }}" label="invoice number"
                            name='invoice_number' placeholder="invoice _number" />
                    </div>
                </div>

                <x-form-select name="payment_method_id" id="payment_method_id" label='Payment Method ' required>
                    @foreach ($paymentMethods as $paymentMethod)
                        <option @if (isset($expense) &&
                                ($expense->payment_method == $paymentMethod->id || old('payment_method_id') == $paymentMethod->id)) selected="selected" @endif
                            @if (!isset($expense) && Auth::user()->employee?->payment_method == $paymentMethod->id) selected="selected" @endif value="{{ $paymentMethod->id }}">
                            {{ $paymentMethod->name }}
                        </option>
                    @endforeach
                </x-form-select>

                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($expenses) && $expenses->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($expenses) && $expenses->status == 'inactive') selected @endif value="inactive">
                        {{ __('Inactive') }}</option>
                </x-form-select>
            </div>
            <x-modal-footer />
        </form>
    </x-modal>
@endsection
@section('js')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
        $(document).ready(function() {
            $("#status,#branch_id,#expense_type_id,#payment_method_id").select2({
                dropdownParent: $("#expensesForm")
            });
            $(document).on('click', '.delete-this-expense', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this Expense?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel!",
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-2', // Red button for confirmation
                        cancelButton: 'btn btn-secondary' // Gray button for cancel
                    },
                    buttonsStyling: false // Disable default SweetAlert styles and use Bootstrap 4
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            method: "DELETE",
                            url: url,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            success: function(msg) {
                                window.location.href =
                                    "{{ route('expenses.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Expense is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
