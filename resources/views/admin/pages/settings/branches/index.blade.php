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

 <!-- start filters -->
<div class="tools dropdown d-flex justify-content-end">
    <div class="dropdown">
        <button class="btn btn-primary btn-sm mx-1 dropdown-toggle " type="button" id="dropdownMenuLink"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-filter"></i> Filters
        </button>
        <div class="dropdown-menu" onclick="event.stopPropagation();" aria-labelledby="dropdownMenuLink" style="width: 300px; height:auto">
            <h3 class="col-12">Filters</h3>

            <!-- start businessCategory filter -->
            <div class="col-12 form-group dropdown-item d-flex flex-column mb-0" style="min-width: 100px;">
                <label for="status">Status</label><br>
                <select name="status" data-placeholder="Select" class="js-example-basic-single fs-xs text-muted form-select-sm"
                    id="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <!-- end businessCategory filter -->

            <!-- start insuranceCompanies filter -->
            <div class="col-12 form-group dropdown-item d-flex flex-column mb-0" style="min-width: 100px;">
                <label for="insuranceCompanies_id">Lead Insurance Companies</label><br>
                <select name="created_by[]" data-placeholder="Select" multiple class="form-select form-select-sm   js-example-basic-multiple"
                    id="created_by">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- end insuranceCompanies filter -->
        </div>
    </div>
</div>
<!-- end filters -->
                </div>
            </div>
            <!-- end filters -->
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
                    <option @if (old('status') == 'active') selected @endif value="active">
                        {{ __('Active') }}</option>
                    <option @if (old('status') == 'inactive') selected @endif value="inactive">
                        {{ __('Inactive') }}</option>
                </x-form-select>
            </div>
            <x-modal-footer />
        </form>


    </x-modal>
@endsection
@section('js')

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

<script>
$(document).ready(function() {
    let table; // Declare the variable to hold the DataTable instance

    function initializeBranchTable() {
        // Check if DataTable is already initialized and destroy it
        if ($.fn.DataTable.isDataTable('#branch-table')) {
            $('#branch-table').DataTable().clear().destroy();
        }

        // Reinitialize DataTable and assign it to the `table` variable
        table = $('#branch-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('branches.index') }}",
                data: function(d) {
                    d.created_by = $('#created_by').val();
                    d.status = $('#status').val();
                }
            },
            dom: '<B><"d-flex w-100 py-2  align-items-center justify-content-between"lf>rtip', // Define layout for buttons, filters, and table
            buttons: [
                {
                    extend: 'excel',
                    exportOptions: {
                        columns: ':not(:nth-last-child(-n+3))' // Exclude the last 3 columns
                    }
                },
                {
                    extend: 'csv',
                    exportOptions: {
                        columns: ':not(:nth-last-child(-n+3))' // Exclude the last 3 columns
                    }
                },
                {
                    extend: 'pdf',
                    exportOptions: {
                        columns: ':not(:nth-last-child(-n+3))' // Exclude the last 3 columns
                    }
                },
                {
                    extend: 'print',
                    exportOptions: {
                        columns: ':not(:nth-last-child(-n+3))' // Exclude the last 3 columns
                    }
                }
            ],
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'manager_id' },
                { data: 'phone' },
                { data: 'email' },
                { data: 'address' },
                { data: 'status' },
                { data: 'created_by' },
                { data: 'created_at' },
                { data: 'updated_at' },
                { data: 'action' },
            ],
            columnDefs: [
                {
                    targets: [0, 7, 9], // Disable sorting and searching on columns 0, 7, 9
                    orderable: false,
                    searchable: false,
                }
            ],
            order: [[0, 'desc']], // Default ordering
        });
    }

    // Initialize the DataTable
    initializeBranchTable();

    // Reload table data when filters change
    $('#created_by, #status').on('change', function() {
        if (table) {
            table.ajax.reload(null, false); // Reload table data without resetting pagination
        }
    });
});
</script>



@endsection
