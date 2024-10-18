@extends('admin.layouts.app')
@section('title')
{{ __('Home Page ') }}

@endsection
@section('content')
{{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Home">
        <x-breadcrumb-item>{{ __('Home') }}</x-breadcrumb-item>
    </x-breadcrumb>
{{-- End breadcrumbs --}}

@endsection
