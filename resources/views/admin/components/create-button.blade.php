@props(['route', 'title'])

<a class="btn btn-success" href="{{route($route) }}">
    <i class="bi bi-plus-lg me-2"></i>
    {{ __(Str::ucfirst($title)) }}
</a>
