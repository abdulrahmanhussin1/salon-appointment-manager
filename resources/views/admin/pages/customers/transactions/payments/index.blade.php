@extends('admin.layouts.app')
@section('title')
    {{ __('Customers Payments Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="customers">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Customers Payments') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('customer_transactions.store_customer_payment'))
                <x-modal-button title="Payment" target="customerPaymentModal"><i
                        class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => 'responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="customerPaymentModal" title="Create Payment">
        <form action="{{ route('customer_transactions.store_customer_payment') }}" method="POST" id="customerPaymentForm"
            enctype="multipart/form-data">
            @csrf
            <div class="modal-body">

                <x-form-select name="customer_id" id="customer_id" label='Customers' required>
                    <option value="">{{ __('Select one Customer') }}</option>
                    @foreach ($customers as $customer)
                        <option @if (isset($invoice) && ($invoice->customer_id == $customer->id || old('customer_id') == $customer->id)) selected="selected" @endif
                            @if (!isset($invoice) && Auth::user()->employee?->customer_id == $customer->id) selected="selected" @endif value="{{ $customer->id }}">
                            {{ $customer->name }} - {{ $customer->phone }}
                        </option>
                    @endforeach
                </x-form-select>

                <x-input type='text' value="{{ old('amount') }}" label="Amount" name='amount'
                    placeholder='Amount' id="amount"     oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required />

                <x-form-description value="{{ old('notes') }}" label="notes" name='notes' placeholder='Notes' />


            </div>
            <x-modal-footer />
        </form>


    </x-modal>
@endsection
@section('js')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
        $(document).ready(function() {
            $("#customer_id").select2({
                dropdownParent: $("#customerPaymentModal")
            });
            $(document).on('click', '.delete-this-customer', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this customer?",
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
                                    "{{ route('customers.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Customer is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
