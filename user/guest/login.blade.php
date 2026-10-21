{{-- @extends('layouts.admin.guest.app')

@section('head_css')
    <style>
        .card {
            box-shadow: none;
        }

        .card-body {
            background: linear-gradient(180deg, #2f41ff 0%, #505ffd 100%);
        }

        .form-label,
        .form-check-label {
            color: #FFFFFF;
        }

        .account-pages {
            margin-top: 10%;
        }

        .login-button {
            border-radius: 50px;
            width: 40%;
            background-color: #2f41ff !important;
            color: white;

            text-align: center;
            padding: 10px 20px;
            border: solid 1px #2f41ff;
        }
    </style>
@endsection

@section('content')
    <div class="account-pages">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">

                        <div class="card-body pt-0">
                            <div class="auth-logo text-center">
                                <a href="{{ route('admin.login') }}" class="auth-logo-dark">
                                    <div class="mb-3 mt-2">
                                        <span class="rounded-circle ">
                                            <img src="{{ checkFileExist(config('general_settings.SITE_LOGO')) }}"
                                                alt="" height="120" width="220">
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="p-2">
                                <form id="login-form" class="form-horizontal" action="{{ route('user.login.post') }}"
                                    method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username/Email</label>
                                        <input type="text" class="form-control" id="username"
                                            placeholder="Enter username/email" name="username">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" class="form-control" placeholder="Enter password"
                                                name="password" aria-label="Password" aria-describedby="password-addon">
                                            <button class="btn btn-light " type="button" id="password-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember-check" name="remember">
                                        <label class="form-check-label" for="remember-check">
                                            Remember me
                                        </label>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-center">
                                        <button id="login-btn" class="btn btn-primary waves-effect waves-light login-button"
                                            type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                                aria-hidden="true"></span>
                                            Log In
                                        </button>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <a href="{{ route('user.password.request') }}" class="text-white"><i
                                                class="mdi mdi-lock me-1"></i> Forgot your password?</a>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <a href="{{ route('user.signup') }}" class="text-white"><i
                                                class="mdi mdi-login me-1"></i> Sign In here</a>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="mt-5 text-center text-white">
                        <div>
                            <p>©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script>
                                <i class="mdi mdi-heart text-danger"></i> by <span
                                    class="text-primary">{{ config('general_settings.SITE_NAME') }}</span>
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <script>
        $(document).ready(function() {
            $("#login-form").validate({
                rules: {
                    username: {
                        required: true,
                    },
                    password: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    username: {
                        required: "{{ __('admin.errors.username_or_email.required') }}",
                    },
                    password: {
                        required: "{{ __('admin.errors.password.required') }}",
                        minlength: "{{ __('admin.errors.password.minlength') }}"
                    }
                },
                errorClass: "text-danger",
                errorElement: "small",
                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "password") {
                        error.insertAfter(".auth-pass-inputgroup");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    // Show loader
                    const loginBtn = $("#login-btn");
                    loginBtn.prop("disabled", true);
                    loginBtn.find(".spinner-border").removeClass("d-none");
                    loginBtn.contents().last().replaceWith("");
                    loginBtn.contents().last().replaceWith(" <i class='mdi mdi-spin me-1'></i>");

                    form.submit();
                }
            });
        });
    </script>
@endsection --}}

@extends('layouts.admin.guest.app')

@section('head_css')
    <style>
        body {
            background: #f5f7fb;
        }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
        }

        .auth-logo img {
            height: 70px;
        }

        .auth-title {
            font-weight: 600;
            font-size: 22px;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .auth-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 25px;
        }

        .form-control {
            height: 46px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: none;
        }

        .login-btn {
            background: #4f46e5;
            border: none;
            height: 46px;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
        }

        .login-btn:hover {
            background: #4338ca;
        }

        .auth-footer a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
@endsection

@section('content')
    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="text-center auth-logo mb-4">
                <img src="{{ checkFileExist(config('general_settings.SMALL_SITE_LOGO')) }}">
            </div>

            <h4 class="auth-title text-center">Welcome back</h4>
            <p class="auth-subtitle text-center">
                Login to your account
            </p>

            <form id="login-form" method="POST" action="{{ route('user.login.post') }}">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">Username/Email</label>
                    <input type="text" class="form-control" id="username" placeholder="Enter username/email"
                        name="username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group auth-pass-inputgroup">
                        <input type="password" class="form-control" placeholder="Enter password" name="password"
                            aria-label="Password" aria-describedby="password-addon">
                        <button class="btn btn-light " type="button" id="password-addon"><i
                                class="mdi mdi-eye-outline"></i></button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <a href="{{ route('user.password.request') }}">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary login-btn">
                    Login
                </button>
            </form>

            <div class="text-center mt-4 auth-footer">
                <span>Don't have an account?</span>
                <a href="{{ route('user.signup') }}">Create account</a>
            </div>

            <div class="text-center mt-4 text-muted" style="font-size: 13px;">
                © {{ date('Y') }} {{ config('general_settings.SITE_NAME') }}
            </div>

        </div>
    </div>
@endsection

@section('page_script')
    <script>
        $(document).ready(function() {
            $("#login-form").validate({
                rules: {
                    username: {
                        required: true,
                    },
                    password: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    username: {
                        required: "{{ __('admin.errors.username_or_email.required') }}",
                    },
                    password: {
                        required: "{{ __('admin.errors.password.required') }}",
                        minlength: "{{ __('admin.errors.password.minlength') }}"
                    }
                },
                errorClass: "text-danger",
                errorElement: "small",
                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "password") {
                        error.insertAfter(".auth-pass-inputgroup");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    // Show loader
                    const loginBtn = $("#login-btn");
                    loginBtn.prop("disabled", true);
                    loginBtn.find(".spinner-border").removeClass("d-none");
                    loginBtn.contents().last().replaceWith("");
                    loginBtn.contents().last().replaceWith(" <i class='mdi mdi-spin me-1'></i>");

                    form.submit();
                }
            });
        });
    </script>
@endsection
