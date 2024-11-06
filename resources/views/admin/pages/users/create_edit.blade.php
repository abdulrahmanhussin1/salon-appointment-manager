@extends('admin.layouts.app')
@section('title')
    {{ isset($user) ? __('Edit User ') : __('Create User') }}
@endsection
@section('css')
    <link href="{{ asset('admin-assets/assets/vendor/choices/choices.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-assets/assets/vendor/choices/theme.min.css') }}" type="text/css" rel="stylesheet" id="style-default">
    <style>
        .custom-avatar {
            display: inline-block;
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
        }

        .custom-avatar img {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            transition: opacity 0.25s;
            display: block;
        }

        .custom-avatar .overlay {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .custom-avatar:hover img,
        .custom-avatar:hover .overlay {
            opacity: 1;
        }

        .custom-avatar .icon {
            color: #ffffff;
            font-size: 32px;
        }
    </style>
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="User">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('users.index') }}">{{ __('Users') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($user) }}">
            {{ isset($user) ? __('Edit :type', ['type' => $user->name]) : __('Create New User') }}
        </x-breadcrumb-item>


    </x-breadcrumb>
{{-- End breadcrumbs --}}
    <div class="container">
        <div class="card radius-15 border-lg-top-primary">
            <div class="card-title">
                <h4 class="m-3 mb-0">{{ isset($user) ? __('Edit :type', ['type' => $user->name]) : __('Create New User') }}
                </h4>
            </div>
            <hr>
            <form method="POST"
                action="{{ isset($user) ? route('users.update', ['user' => $user]) : route('users.store') }}"
                enctype="multipart/form-data" id="userForm">
                @csrf
                @if (isset($user))
                    @method('PUT')
                @endif
                <div class="card-body">
                    <div class="col-lg-12">
                        <x-form-personal-image :src="isset($user) && isset($user->photo)
                            ? asset('storage/' . $user->photo)
                            : asset('admin-assets/assets/img/avatar.jpg')" name="photo" />
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <x-input type='text' :value="isset($user) ? $user->name : old('name')" label="Name" name='name'
                                placeholder='User Name' id="name" oninput="" required />
                        </div>
                        <div class="col-6">
                            <x-input type='email' :value="isset($user) ? $user->email : old('email')" label="email" name='email'
                                placeholder='User Email' id="email" oninput="" required />
                        </div>
                    </div>

                    @if(!isset($user))
                    <div class="row">
                        <div class="col-6">
                            <x-input type="password" :value="old('password')" label="password" name='password' placeholder='Your Password'
                                id="password" />
                        </div>
                        <div class="col-6">
                            <x-input type="password" :value="old('password_confirmation')" label="Confirm Password" name='password_confirmation'
                                placeholder='Confirm Your Password ' id="password_confirmation" />
                        </div>
                    </div>
                    @endif
                    <div class="row">
                        <div class="col-6">
                            <x-form-select name='status' id="status" label="status" required>
                                <option @if (isset($user) && $user->status == 'active') selected @endif value="active">
                                    {{ __('Active') }}</option>
                                <option @if (isset($user) && $user->status == 'inactive') selected @endif value="inactive">
                                    {{ __('Inactive') }}</option>
                            </x-form-select>
                        </div>
                        <div class="col-6">
                            <x-form-select name="role_id" id="role_id" label='Role' required>
                                @foreach (\Spatie\Permission\Models\Role::where('status', 'active')->pluck('name', 'id')->toArray() as $id => $name)
                                   e <option value="">Select one Role</option>
                                <option
                                        @if (isset($user) && in_array($id, $user->roles()->pluck('id')->toArray())) selected="selected"
                            @elseif(old('role_id') && in_array($id, old('role_id'))) selected="selected" @endif
                                        value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-form-select>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <x-submit-button label='Confirm' />

                </div>
            </form>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function openFileInput() {
            document.getElementById('fileInput').click();
        }

        function handleFileSelect() {
            const fileInput = document.getElementById('fileInput');
            const avatarImage = document.getElementById('avatar');

            const selectedFile = fileInput.files[0];

            if (selectedFile) {

                const reader = new FileReader();

                reader.onload = function(e) {
                    avatarImage.src = e.target.result;
                };

                reader.readAsDataURL(selectedFile);
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            $('#userForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 150
                    },
                    email: {
                        required: true,
                        email: true,
                    },
                    password: {
                        required: true,
                        minlength: 8
                    },
                    password_confirmation: {
                        required: true,
                        equalTo: '#password'
                    },
                    photo: {
                        accept: 'image/png,image/jpeg,image/gif,image/svg',
                        filesize: 2048
                    },
                    title_type: {
                        required: true,
                    },
                    status: {
                        required: true
                    },
                    'role_id[]': {
                        required: true,
                    }
                },
                messages: {
                    name: {
                        required: 'Please enter your name.',
                        maxlength: 'Name must not exceed 150 characters.'
                    },
                    email: {
                        required: 'Please enter your email address.',
                        email: 'Please enter a valid email address.',
                    },
                    password: {
                        required: 'Please enter your password.',
                        minlength: 'Password must be at least 6 characters long.'
                    },
                    password_confirmation: {
                        required: 'Please confirm your password.',
                        equalTo: 'Passwords do not match.'
                    },
                    image: {
                        accept: 'Please upload an photo of type: png, jpg, jpeg, gif, svg.',
                        filesize: 'File size must be less than 2MB.'
                    },
                    title_type: {
                        required: 'Please enter title.',

                    },
                    status: {
                        required: 'Please select a status.'
                    },
                    'role_id[]': {
                        required: 'Please select a role.'
                    }
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
