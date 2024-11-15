@extends('admin.layouts.app')
@section('title')
    {{ __('Tools Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Tools">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Tools') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('tools.create'))
                <x-modal-button title="Tool" target="toolModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="toolModal" title="Create Tool">
        <form action="{{ route('tools.store') }}" method="POST" id="toolForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-input type='text' value="{{ old('name') }}" label="Name" name='name' placeholder='Tool Name'
                    id="name" oninput="" required />
<x-form-select name="branch_id" id="branch_id" label='Branch' required>
                                <option value="">{{ __('Select one Branch') }}</option>
                                @foreach ($branches as $branch)
                                    <option @if (isset($tool) && ($tool->branch_id == $branch->id || old('branch_id') == $branch->id)) selected="selected" @endif
                                        value="{{ $branch->id }}">{{ $branch->name }}
                                    </option>
                                @endforeach
                            </x-form-select>
                <x-form-description value="{{ old('description') }}" label="Description" name='description'
                    placeholder='Tool Description' />
                        <x-form-select name='status' id="status" label="status" required>
                            <option @if (old('status') == 'active') selected @endif value="active">
                                {{ __('Active') }}</option>
                            <option @if (old('status') == 'inactive') selected @endif value="inactive">
                                {{ __('Inactive') }}</option>
                        </x-form-select>

                <x-file-input name='image' id="image" label="Image" />
            </div>
            <x-modal-footer />
        </form>


    </x-modal>
@endsection
@section('js')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
        $(document).ready(function() {
            $("#status,#branch_id").select2({
                dropdownParent: $("#toolModal")
            });
            $(document).on('click', '.delete-this-tool', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this Tool?",
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
                                window.location.href = "{{ route('tools.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Tool is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
