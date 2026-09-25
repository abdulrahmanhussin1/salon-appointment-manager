@props(['label','name','value' , 'placeholder'])

<div class="form-group mb-3">
    <label for="{{ $name }}" class="form-label">{{ __(Str::ucfirst($label)) }}</label>
    <textarea class="form-control @error($name) is-invalid @enderror" name="{{ $name }}" id="{{ $name }}"
        placeholder="{{ $placeholder ? __($placeholder) : '' }}">{{ $value }}</textarea>
    @error($name)
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
