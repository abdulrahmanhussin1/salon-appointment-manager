@extends('admin.layouts.app')
@section('title')
    {{ __('Suppliers Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Suppliers">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Suppliers') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('suppliers.create'))
                <x-modal-button title="Supplier" target="supplierModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="supplierModal" title="Create Supplier">
        <form action="{{ route('suppliers.store') }}" method="POST" id="supplierForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-input type='text' value="{{ old('name') }}" label="Name" name='name'
                    placeholder='supplier Name' id="name" oninput="" required />
                <x-input type="email" value="{{ old('email') }}" label="Email" name='email'
                    placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />
                <x-input type="text" value="{{ old('phone') }}" label="phone" id="phone" name='phone'
                    placeholder="phone  Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />
                <x-form-description value="{{ old('address') }}" label="address" name='address'
                    placeholder='supplier Address' />
                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($role) && $role->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($role) && $role->status == 'inactive') selected @endif value="inactive">
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
                dropdownParent: $("#supplierModal")
            });
            $(document).on('click', '.delete-this-supplier', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this supplier?",
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
                                "{{ route('suppliers.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Supplier is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
