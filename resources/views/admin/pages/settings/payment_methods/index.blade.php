@extends('admin.layouts.app')
@section('title')
    {{ __('Payment Methods Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Payment Methods">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Payment Methods') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('payment_methods.create'))
                <x-modal-button title="Payment Method" target="paymentMethodsModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="paymentMethodsModal" title="Create Payment Method">
        <form action="{{ route('payment_methods.store') }}" method="POST" id="paymentMethodsForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-input type='text' value="{{ old('name') }}" label="Name" name='name'
                    placeholder='Payment Method Name' id="name" oninput="" required />

                <x-form-description value="{{ old('description') }}" label="description" name='description'
                    placeholder='Payment Method description' />
                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($paymentMethods) && $paymentMethods->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($paymentMethods) && $paymentMethods->status == 'inactive') selected @endif value="inactive">
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
            $("#status").select2({
                dropdownParent: $("#paymentMethodsModal")
            });
            $(document).on('click', '.delete-this-payment_method', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this Payment Method?",
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
                                "{{ route('payment_methods.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Payment Method is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
