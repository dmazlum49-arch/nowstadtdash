@extends('layouts.admin.app')
@section('head_css')
    {{-- Toggle Switch CSS --}}
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 55px;
            height: 28px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }
    </style>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h3 class="mb-3">Two-Factor Authentication (2FA)</h3>
                            <p class="text-muted">
                                Protect your account using Google Authenticator.
                            </p>

                            {{-- Toggle Section --}}
                            <form id="toggle-form" action="{{ route('user.2fa.toggle') }}" method="POST">
                                @csrf

                                <div class="d-flex align-items-center mb-4">
                                    <label class="me-3 fw-bold">Enable 2FA</label>

                                    <label class="switch">
                                        <input type="checkbox" name="is_2fa" id="is_2fa"
                                            {{ Auth::user()->is_two_factor_authentication ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </form>

                            {{-- 2FA Enabled --}}
                            @if (Auth::user()->is_two_factor_authentication)
                                <div id="enabled-box" class="border rounded p-4 bg-light">
                                    <h5 class="text-success"><i class="mdi mdi-check-circle"></i> 2FA Enabled</h5>
                                    <p>Your account is protected with Google Authenticator.</p>

                                    <form method="POST" action="{{ route('user.2fa.disable') }}">
                                        @csrf
                                        <button class="btn btn-danger mt-2">Disable 2FA</button>
                                    </form>
                                </div>
                            @endif


                            {{-- 2FA Setup Box (Only when enabling, before OTP verify) --}}
                            @if (!$user->is_two_factor_authentication && $user->google2fa_secret)
                                <div id="setup-box" class="border rounded p-4 mt-4">

                                    <h5 class="mb-2 fw-bold">Complete Setup</h5>
                                    <p class="text-muted">Scan this QR code using Google Authenticator.</p>

                                    <div class="text-center mb-3">
                                        {!! $qrCode !!}
                                    </div>

                                    <p class="mb-1 fw-bold">Manual Secret Key:</p>
                                    <div class="alert alert-info">{{ $secret }}</div>

                                    <form id="main-form" method="POST" action="{{ route('user.2fa.setup.verify') }}">
                                        @csrf

                                        <label class="fw-bold">Enter OTP</label>
                                        <input type="text" name="otp" class="form-control"
                                            placeholder="please enter 6-digit code" required>

                                        <button class="btn btn-success mt-3 w-100">Verify & Enable 2FA</button>
                                    </form>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page_script')
    {{-- Auto Submit Toggle --}}
    <script>
        document.getElementById('is_2fa').addEventListener('change', function() {
            document.getElementById('toggle-form').submit();
        });
    </script>

    <script>
        $(document).ready(function() {
            $("#main-form").validate({
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
