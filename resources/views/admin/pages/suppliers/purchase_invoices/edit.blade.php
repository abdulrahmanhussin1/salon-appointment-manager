@extends('admin.layouts.app')
@section('title', 'Edit Purchase Invoice')
@section('content')
    {{-- Breadcrumbs --}}
    <x-breadcrumb pageName="Edit Invoice">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('purchase_invoices.index') }}">{{ __('Purchase Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Edit Invoice') }}</x-breadcrumb-item>
    </x-breadcrumb>

    <section class="section">
        <div class="container">
            @include('admin.layouts.alerts')
            @include('admin.pages.suppliers.purchase_invoices.includes.form')

        </div>
    </section>
@endsection
@section('js')
<script>
        const products = @json($products);

</script>
<script src="{{ asset('admin-assets/assets/js/purchase_invoice.js') }}"></script>
@endsection
