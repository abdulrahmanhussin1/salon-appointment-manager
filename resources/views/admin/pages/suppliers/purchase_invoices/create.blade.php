@extends('admin.layouts.app')
@section('title')
    {{ __('New Purchase Invoice ') }}
@endsection
@section('css')
    <style>
        /* Custom Styles for Forms */
        form label {
            font-weight: bold;
        }

        table thead th {
            text-align: center;
            vertical-align: middle;
        }

        table tbody td {
            vertical-align: middle;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }


        /* Add some spacing */
    </style>
@endsection
@section('content')
    <x-breadcrumb pageName="Purchase Invoice">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('purchase_invoices.index') }}">{{ __('Purchase Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($product) }}">
            {{ __('Create New Invoice') }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}
    <div class="card">
        <div class="card-header text-dark">
            <h4 class="mb-0">Create Purchase Invoice</h4>
        </div>
        @include('admin.layouts.alerts')
        <div class="card-body">
    @include('admin.pages.suppliers.purchase_invoices.includes.form')

        </div>
    </div>
@endsection

@section('js')
<script>
        const products = @json($products);

</script>
 <script src="{{ asset('admin-assets/assets/js/purchase_invoice.js') }}"></script>
@endsection
