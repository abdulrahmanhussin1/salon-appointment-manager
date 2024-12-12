@props([
    'label' => false,
    'name', 'id' => false,
    'required' => false,
    'class' => false,
    ])

<div class="form-group mb-3">
    @if ($label)
        <label class="form-label {{ $class ? $class : '' }}" for="{{ $id ? $id : $name }}">
            {{ __(Str::ucfirst($label)) }}
        </label>
    @endif
    <select class="js-example-basic-single fs-xs text-muted form-select-sm @error($name) is-invalid @enderror"
            name="{{ $name }}"
            id="{{ $id ? $id : $name }}"
            @if ($required) required @endif
            style="width: 100%">
        {{ $slot }}
    </select>
    @error($name)
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
