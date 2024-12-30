@extends('admin.layouts.app')
@section('title')
    {{ __('Product Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Product">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Product') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('products.create'))
                <x-create-button title="Product" route="products.create"><i class="bi bi-plus-lg me-2"></i></x-create-button>
            @endif
        </div>

        <form method="get" id="expenseForm" class="my-3 bg-white my-3 rounded-2 shadow px-5 py-2 pt-4" action="{{ route('products.index') }}">
            <div class="">
                <div class="row">

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
@endsection
@section('js')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
        $(document).ready(function() {
            $(document).on('click', '.delete-this-product', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this Product?",
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
                                "{{ route('products.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Product is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
