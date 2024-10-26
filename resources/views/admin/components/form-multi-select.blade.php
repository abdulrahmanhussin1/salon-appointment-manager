@props(['label', 'name', 'id' => false, 'required' => false])


<div class="form-group  mb-3">
    <label for="{{ $id }}" class="form-label">{{ __($label) }}*</label>
    <select class="form-select js-example-basic-multiple fs-xs form-select-sm @error($name) is-invalid @enderror" style="width: 100%"
        data-options='{"removeItemButton":true,"placeholder":true}'
        data-placeholder="{{ __('Select :type', ['type' => __($label)]) }}" @if($required ) required @endif
        multiple="multiple" name="{{ $name }}" id="{{ $id }}">
        {{ $slot }}
    </select>
    @error($name)
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror
</div>
