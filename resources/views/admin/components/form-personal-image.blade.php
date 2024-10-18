@props(['name','src'])

<div class="form-group mb-3 text-center">
    <div class="custom-avatar">
        <img src="{{ $src }}" alt="Avatar" id="avatar">
        <input type="file" name="{{ $name }}" id="fileInput" class="@error('image') is-invalid @enderror"
            onchange="handleFileSelect()">
        <div class="overlay" onclick="openFileInput()">
            <i class="icon fa fa-pencil"></i>
        </div>
        @error($name)
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>
</div>
