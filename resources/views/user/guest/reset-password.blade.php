@extends('layouts.admin.guest.app')

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
                                        <span class="">
                                            <img src="{{ checkFileExist(config('general_settings.SITE_LOGO')) }}"
                                                alt="" height="120" width="220">
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="p-2">
                                <form id="forgot-form" class="form-horizontal" action="{{ route('user.password.update') }}"
                                    method="POST">
                                    @csrf
                                    <input type="hidden" name="reset_token" value="{{ $token }}">
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" class="form-control" placeholder="Enter password"
                                                id="password" name="password" aria-label="Password"
                                                aria-describedby="password-addon">
                                            <button class="btn btn-light " type="button" id="password-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="input-group auth-cpass-inputgroup">
                                            <input type="password" class="form-control" placeholder="Enter confirm password"
                                                name="confirmed" aria-label="Password" aria-describedby="cpassword-addon">
                                            <button class="btn btn-light " type="button" id="cpassword-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-center">
                                        <button id="forgot-btn"
                                            class="btn btn-primary waves-effect waves-light login-button" type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                                aria-hidden="true"></span>
                                            Reset Password
                                        </button>
                                    </div>
                                    <div class="mt-4 text-center">
                                        <a href="{{ route('user.login') }}" class="text-white"><i
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
            $("#forgot-form").validate({
                rules: {
                    password: {
                        required: true,
                        minlength: 6
                    },
                    confirmed: {
                        required: true,
                        equalTo: "#password"
                    },
                },
                messages: {
                    password: {
                        required: "{{ __('admin.errors.password.required') }}",
                        minlength: "{{ __('admin.errors.password.minlength') }}"
                    },
                    confirmed: {
                        required: "{{ __('admin.errors.confirm_password.required') }}",
                        equalTo: "{{ __('admin.errors.confirm_password.equal_to') }}"
                    },
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
                        error.insertAfter(".auth-cpass-inputgroup");
                    }
                },
                submitHandler: function(form) {
                    const loginBtn = $("#forgot-btn");
                    loginBtn.prop("disabled", true);
                    loginBtn.find(".spinner-border").removeClass("d-none");
                    loginBtn.contents().last().replaceWith(
                        " <i class='mdi mdi-spin me-1'></i> Sending ...");

                    form.submit();
                }
            });
        });
    </script>
@endsection
