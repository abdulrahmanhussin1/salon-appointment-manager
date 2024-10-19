@props(['name','id'=>false,'label'])

<div class="form-group mb-3">
    <label class="form-label" @if ($id) for="{{ $id }}"@endif>{{ $label }}</label>
    <input class="form-control form-control-sm @error($name) is-invalid @enderror" type="file" name="{{ $name }}" @if ($id) id="{{ $id }}"@endif />
    @error($name)
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror
  </div>
