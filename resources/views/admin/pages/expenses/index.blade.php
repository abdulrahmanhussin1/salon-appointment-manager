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
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="ExpensesModal" title="Create Expense">
        <form action="{{ route('expenses.store') }}" method="POST" id="ExpensesForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-form-select name="expense_type_id" id="expense_type_id" label='expense Level' required>
                    <option value="">{{ __('Select one Expense Level') }}</option>
                    @foreach ($expenseTypes as $expenseType)
                        <option @if (isset($expense) &&
                                ($expense->expense_type_id == $expenseType->id || old('expense_type_id') == $expenseType->id)) selected="selected" @endif
                            value="{{ $expenseType->id }}">{{ $expenseType->name }}
                        </option>
                    @endforeach
                </x-form-select>

                <x-form-description value="{{ old('description') }}" label="description" name='description'
                    placeholder='Expense description' />

                    <div class="row">
                        <div class="col-6">
                                                <x-input type='text' value="{{ old('amount') }}" label="Amount" name='amount' placeholder='Amount' oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                        </div>
                        <div class="col-6">
                                                <x-input type='text' value="{{ old('paid_amount') }}" label="paid amount" name='paid_amount' placeholder='paid_amount' oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />

                        </div>
                    </div>
                    <x-input type='date' value="{{ old('paid_at') }}" label="paid_at" name='paid_at' placeholder="paid_at" />


                <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                    <option value="">{{ __('Select one Branch') }}</option>
                    @foreach ($branches as $branch)
                        <option @if (isset($product) && ($product->branch_id == $branch->id || old('branch_id') == $branch->id)) selected="selected" @endif
                            @if (!isset($invoice) && Auth::user()->expense?->branch_id == $branch->id) selected="selected" @endif value="{{ $branch->id }}">
                            {{ $branch->name }}
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
            $("#status,#branch_id,#expense_type_id").select2({
                dropdownParent: $("#ExpensesModal")
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
