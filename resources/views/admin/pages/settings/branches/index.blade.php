@extends('admin.layouts.app')
@section('title')
    {{ __('Branches Page ') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Branches">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Branches') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('branches.create'))
                <x-modal-button title="branch" target="branchModal"><i class="bi bi-plus-lg me-2"></i></x-modal-button>
            @endif
        </div>
        @include('admin.layouts.alerts')
        <div>
            {{ $dataTable->table(['class' => ' responsive table fs--1 mb-0 bg-white my-3 rounded-2 shadow', 'width' => '100%']) }}
        </div>
    </section>


    <x-modal id="branchModal" title="Create Branch">
        <form action="{{ route('branches.store') }}" method="POST" id="branchForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-input type='text' value="{{ old('name') }}" label="Name" name='name' placeholder='Branch Name'
                    id="name" oninput="" required />

                <x-input type="text" value="{{ old('phone') }}" label="phone" id="phone" name='phone'
                    placeholder="phone  Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" />

                <x-input type="email" value="{{ old('email') }}" label="Email" name='email'
                    placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />

                <x-form-description value="{{ old('address') }}" label="address" name='address'
                    placeholder='Branch address' />
                <x-form-select name='manager_id' id="manager_id" label="manager">
                    <option value="">{{ __('Select one Manager') }}</option>
                    @foreach ($managers as $manager)
                        <option @if (isset($branch) && ($branch->manager_id == $manager->id || old('manager_id') == $manager->id)) selected="selected" @endif value="{{ $manager->id }}">
                            {{ $manager->name }}</option>
                    @endforeach
                </x-form-select>
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
            $("#status,#manager_id").select2({
                dropdownParent: $("#branchModal")
            });
            $(document).on('click', '.delete-this-branch', function(e) {
                e.preventDefault();
                let el = $(this);
                let url = el.attr('data-url');
                let id = el.attr('data-id');

                Swal.fire({
                    title: "Are you sure you really want to delete this branch?",
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
                                window.location.href = "{{ route('branches.index') }}";
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire({
                            title: "Cancelled",
                            text: "Branch is safe :)",
                            icon: "error"
                        });
                    }
                });
            });
        });
    </script>
@endsection
