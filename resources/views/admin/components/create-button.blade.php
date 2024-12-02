@props(['route', 'title','class' => 'btn-success'])

<a class="btn {{ $class }} btn-sm" href="{{route($route) }}">
    <i class="bi bi-plus-lg me-2"></i>
    {{ __(Str::ucfirst($title)) }}
</a>
