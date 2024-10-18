@props(['route', 'title'])

<a class="btn btn-success" href="{{route($route) }}">
    <i class="fa-solid fa-plus me-2 fa-sm" style="color: #ffffff;"></i> {{ __(Str::ucfirst($title)) }}
</a>
