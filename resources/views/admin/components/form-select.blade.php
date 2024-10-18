@props(['label', 'name', 'id' => false, 'required' => false])

<div class="form-group  mb-3">
    <label>{{ __(Str::ucfirst($label)) }}</label>
    <select class="form-select js-example-basic-single fs-xs form-select-sm @error($name) is-invalid @enderror"
        name="{{ $name }}" @if ($id) id="{{ $id }}" @endif
        @if ($required) required @endif>
        {{ $slot }}
    </select>
    @error($name)
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
