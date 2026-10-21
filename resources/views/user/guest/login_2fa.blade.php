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
                                <a href="{{ route('admin.password.request') }}" class="auth-logo-dark">
                                    <div class="mb-3 mt-2">
                                        <span class="">
                                            <img src="{{ checkFileExist(config('general_settings.SITE_LOGO')) }}"
                                                alt="" height="120" width="220">
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="card p-4">

                                <h4 class="text-center mb-3">Two-Factor Authentication</h4>
                                <p class="text-center">Enter the 6-digit code from your Authenticator App</p>

                                <form id="otp-form" method="POST" action="{{ route('user.2fa.login.post') }}">
                                    @csrf

                                    <div class="mb-3">
                                        <input type="text" name="otp" class="form-control" placeholder="Please enter OTP"
                                            required>
                                    </div>

                                    <button  class="btn btn-primary w-100">Verify OTP</button>

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
            $("#otp-form").validate({
                rules: {
                    otp: {
                        required: true,
                        digits: true,
                        minlength: 6,
                        maxlength: 6
                    }
                },
                messages: {
                    otp: {
                        required: "Please enter the OTP",
                        digits: "Please enter only digits",
                        minlength: "OTP must be 6 digits",
                        maxlength: "OTP must be 6 digits"
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
                    error.insertAfter(element);
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
