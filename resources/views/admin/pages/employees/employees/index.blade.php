@extends('admin.layouts.app')
@section('title')
{{ __('Employees Page ') }}

@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Employees">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Employees') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('employees.create'))
                <x-create-button title="Employee" route="employees.create" />
            @endif
        </div>
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>
@endsection
@section('js')
{{ $dataTable->scripts(attributes: ['type' => 'module']) }}

<script>
    $(document).ready(function() {

        $(document).on('click', '.delete-this-employee', function(e) {
            e.preventDefault();
            let el = $(this);
            let url = el.attr('data-url');
            let id = el.attr('data-id');

            Swal.fire({
                title: "Are you sure you really want to delete this Employee?",
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
                            window.location.href = "{{ route('employees.index') }}";
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: "Cancelled",
                        text: "Employee is safe :)",
                        icon: "error"
                    });
                }
            });
        });


    });
</script>
@endsection
