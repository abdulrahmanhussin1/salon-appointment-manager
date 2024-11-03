@props([
    'name',
    'label',
    'type',
    'placeholder',
    'value' => false,
    'oninput' => false,
    'id' => false,
    'required' => false,
    'disabled' => false,
])
<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ Str::ucfirst(__($label)) }}</label>
    <input type="{{ $type }}" name="{{ $name }}"
        class="form-control form-control-sm @error($name) is-invalid @enderror" placeholder="{{ $placeholder }}"
        @if ($id) id="{{ $id }}" @endif
        @if ($required) required @endif
        @if ($value) value="{{ $value }}" @endif
        @if ($oninput) oninput="{{ $oninput }}" @endif
        @if ($disabled) disabled @endif
        >

    @error($name)
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
