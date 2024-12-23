<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $adminPanelSetting->system_name }} | @yield('title')</title>


  <style>
    body {
      overflow-x: auto;
    }
  </style>

    @include('admin.layouts.styles')
  </head>
