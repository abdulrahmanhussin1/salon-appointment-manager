@extends('admin.layouts.app')
@section('title')
{{ __('Users Page ') }}

@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Users">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Users') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-end">
            @if (App\Traits\AppHelper::perUSer('users.create'))
                <x-create-button title="User" route="users.create" />
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

        $(document).on('click', '.delete-this-user', function(e) {
            e.preventDefault();
            let el = $(this);
            let url = el.attr('data-url');
            let id = el.attr('data-id');

            Swal.fire({
                title: "Are you sure you really want to delete this User?",
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
                            window.location.href = "{{ route('users.index') }}";
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: "Cancelled",
                        text: "User is safe :)",
                        icon: "error"
                    });
                }
            });
        });


    });
</script>
@endsection
