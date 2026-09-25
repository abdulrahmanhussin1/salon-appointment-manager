@props(['pageName'])
@php
    $key = Str::ucfirst($pageName).' Page';
    $translated = __($key);
    if ($translated === $key) {
        $translated = __(Str::ucfirst($pageName));
    }
@endphp
<!-- Start  Page Title -->
<div class="pagetitle">
    <h1>{{ $translated }}</h1>
    <nav>
      <ol class="breadcrumb mt-4">
        {{ $slot }}
      </ol>
    </nav>
  </div>
  <!-- End Page Title -->
