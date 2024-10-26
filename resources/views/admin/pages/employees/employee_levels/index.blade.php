@extends('admin.layouts.app')
@section('title')
    {{ __('Employee Levels Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Employee Levels">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Employee Levels') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('employee_levels.create'))
                <x-modal-button title="Employee Level" target="employeeLevelsModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="employeeLevelsModal" title="Create Employee Level">
        <form action="{{ route('employee_levels.store') }}" method="POST" id="employeeLevelsForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-input type='text' value="{{ old('name') }}" label="Name" name='name'
                    placeholder='Employee Level Name' id="name" oninput="" required />

                <x-form-description value="{{ old('description') }}" label="description" name='description'
                    placeholder='Employee Level description' />
                <x-form-select name='status' id="status" label="status" required>
                    <option @if (isset($employeeLevels) && $employeeLevels->status == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (isset($employeeLevels) && $employeeLevels->status == 'inactive') selected @endif value="inactive">
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
                dropdownParent: $("#employeeLevelsModal")
            });
            $(document).on('click', '.delete-this-employee_level', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this Employee Level?",
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
                                "{{ route('employee_levels.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Employee Level is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
