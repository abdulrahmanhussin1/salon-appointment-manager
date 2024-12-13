@extends('admin.auth.layouts.app')

@section('title')
    {{ __('Login Page') }}
@endsection

@section('css')
<style>

</style>

@endsection
@section('content')
    <main>

            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column  justify-content-center">

                            <div class="d-flex justify-content-center py-4">
                                <a href="#" class="logo d-flex align-items-center w-auto">
                                    <img  style="max-height: 50px" src="{{ !empty($adminPanelSetting->system_logo) ? (Storage::exists($adminPanelSetting->system_logo) ? Storage::url($adminPanelSetting->system_logo) : asset('admin-assets/assets/img/avatar.jpg')) : '' }}" alt="">
                                    <span class="d-none d-lg-block">{{ $adminPanelSetting->system_name }}</span>
                                </a>
                            </div><!-- End Logo -->

                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">{{ __('Login to Your Account') }}</h5>
                                        <p class="text-center small">{{ __('Enter your username & password to login') }}</p>
                                    </div>
                                    @include('admin.layouts.alerts')
                                    <form id="loginForm" method="POST" action="{{ route('login') }}">
                                        @csrf
                                        <div class="col-12 mb-3">
                                            <label for="emailInpput" class="form-label">{{ __('Email') }}</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="email" name="email" class="form-control" id="emailInpput"
                                                   value="{{ old('email') }}"  autofocus autocomplete="email" placeholder="name@example.com">
                                                   @error('email')
                                                   <div class="invalid-feedback">
                                                    {{ $message }}
                                                   </div>

                                                   @enderror
                                            </div>
                                        </div>

                                        <div class="col-12 mb-3">
                                            <label for="passwordInput" class="form-label">{{ __('Password') }}</label>
                                            <input type="password" name="password" class="form-control" id="passwordInput" value="{{ old('password') }}"  autocomplete="password"
                                                placeholder="*********" required>
                                                @error('password')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>

                                                @enderror
                                        </div>

                                        {{--  <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember"
                                                    value="true" id="rememberMe">
                                                <label class="form-check-label" for="rememberMe">Remember me</label>
                                            </div>
                                        </div> --}}
                                        <div class="col-12 mb-3">
                                            <button class="btn btn-primary w-100"
                                                type="submit">{{ __('Login') }}</button>
                                        </div>
                                        {{-- <div class="col-12">
                                            <p class="small mb-0">{{ __("Don't have account?") }} <a
                                                    href="pages-register.html">{{ __('Create an account') }}</a>
                                            </p>
                                        </div> --}}
                                    </form>

                                </div>
                            </div>

                           {{--  <div class="credits">
                                <!-- All the links in the footer should remain intact. -->
                                <!-- You can delete the links only if you purchased the pro version. -->
                                <!-- Licensing information: https://bootstrapmade.com/license/ -->
                                <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
                                {{ __('Designed by') }} <a href="#">BODO DEV</a>
                            </div> --}}

                        </div>
                    </div>
                </div>

            </section>

    </main>
    @endsection

    @section('js')
<script>
$(document).ready(function () {
    $('#loginForm').validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 6
            }
        },
        messages: {
            email: {
                required: "{{ __('Please enter your email address.') }}",
                email: "{{ __('Please enter a valid email address.') }}"
            },
            password: {
                required: "{{ __('Please enter your password.') }}",
                minlength: "{{ __('Password must be at least 6 characters long.') }}"
            }
        },
        submitHandler: function(form) {
            form.submit();
        },

        errorClass: "error text-danger",
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
