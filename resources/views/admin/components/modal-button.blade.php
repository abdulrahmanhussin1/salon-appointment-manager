@props(['target','title','class' => 'btn-success'])
<div>
    <button type="button" class="btn {{ $class }} btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $target }}">
        {{ $slot }}
        {{ __(Str::ucfirst($title)) }}
      </button>
</div>
