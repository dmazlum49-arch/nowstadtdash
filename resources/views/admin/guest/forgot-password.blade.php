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
                            <div class="p-2">
                                <div class="alert alert-success text-center mb-4" role="alert">
                                    Enter your Email and instructions will be sent to you!
                                </div>
                                <form id="forgot-form" class="form-horizontal" action="{{ route('admin.password.email') }}"
                                    method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" placeholder="Enter email"
                                            name="email">
                                    </div>
                                    <div class="mt-3 d-flex justify-content-center">
                                        <button id="login-btn" class="btn btn-primary waves-effect waves-light login-button"
                                            type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                                aria-hidden="true"></span>
                                            Reset
                                        </button>
                                    </div>
                                    <div class="mt-4 text-center">
                                        <a href="{{ route('admin.login') }}" class="text-white"><i
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
                    email: {
                        required: true,
                        email: true,
                    },
                },
                messages: {
                    email: {
                        required: "{{ __('admin.errors.email.required') }}",
                        email: "{{ __('admin.errors.email.format') }}",
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
                    error.insertAfter(element);
                },
                submitHandler: function(form) {
                    // Show loader
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
