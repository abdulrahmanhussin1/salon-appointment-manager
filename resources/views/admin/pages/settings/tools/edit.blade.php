@extends('admin.layouts.app')
@section('title')
    {{  __('Edit Tool ')  }}
@endsection
@section('css')
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Tools">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('tools.index') }}">{{ __('Tools') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($tool) }}">
            {{  __('Edit :type', ['type' => $tool->name])  }}
        </x-breadcrumb-item>
    </x-breadcrumb>
{{-- End breadcrumbs --}}

<div class="container ">
    <div class="card radius-15 border-lg-top-primary my-5">
        <div class="card-body">
            <div class="card-title">
                <h4 class="mb-0">
                    {{  __('Edit :type', ['type' => $tool->name])  }}</h4>
            </div>
            <hr>
            <form method="POST" id="toolForm" enctype="multipart/form-data" id="toolForm"
                action="{{ route('tools.update', ['tool' => $tool]) }}">
                @csrf @method('PUT')

                <div class="card-body">
                    <x-input type="text" value="{{ isset($tool) ? $tool->name : old('name') }}" label="Name" name='name' placeholder='Tool Name'
                        id="tool_name" oninput="{{ null }}" required />
                    <x-form-description value="{{ isset($tool) ? $tool->description : old('description') }}"
                        label="Description" name='description' placeholder='Tool Description' />
                    <x-form-select name='status' id="status" label="status" required>
                        <option @if (isset($tool) && $tool->status == 'active') selected @endif value="active">
                            {{ __('Active') }}</option>
                        <option @if (isset($tool) && $tool->status == 'inactive') selected @endif value="inactive">
                            {{ __('Inactive') }}</option>
                    </x-form-select>
                    <x-file-input name='image' id="image" label="Image" />
                    <div class="text-center mt-2">
                        <x-submit-button label='Confirm' />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    $(document).ready(function() {
       $('#toolForm').validate({
           rules: {
               name: {
                   required: true,
                   maxlength: 150
               },
               description: {
                    minlength: 3,
                    maxlength: 500
                },
               status: {
                   required: true
               },
           },
           messages: {
               name: {
                   required: 'Please enter your name.',
                   maxlength: 'Name must not exceed 150 characters.'
               },
               description: {
                    minlength: 'Description must be at least 4 characters',
                    maxlength: 'Description must not exceed 500 characters.'
                },

               status: {
                   required: 'Please select a status.'
               },
           },
           errorClass: "error text-danger fs--1",
           errorElement: "span",
           highlight: function(element, errorClass, validClass) {
               $(element).addClass(errorClass).removeClass(validClass);
               $(element.form).find("label[for=" + element.id + "]").addClass(errorClass);
           },
           unhighlight: function(element, errorClass, validClass) {
               $(element).removeClass(errorClass).addClass(validClass);
               $(element.form).find("label[for=" + element.id + "]").removeClass(errorClass);
           },
       });
   });
</script>
@endsection
