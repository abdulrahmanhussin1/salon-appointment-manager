@props(['target','title'])
<div>
    <button type="button"class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $target }}">
        {{ $slot }}
        {{ __(Str::ucfirst($title)) }}
      </button>
</div>
