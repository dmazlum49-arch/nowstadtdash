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

        .otp_box {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .otp_box input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
        }

        .otp_box input:focus {
            border-color: #2f41ff;
            box-shadow: 0 0 4px rgba(47, 65, 255, 0.5);
        }

        .form-btn-grp {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
        }

        .comn-btn {
            border-radius: 50px;
            padding: 10px 25px;
            font-size: 12px;
            border: none;
            cursor: pointer;
        }

        .no-btn {
            background-color: #fff;
            color: #2f41ff;
            border: 1px solid #fff;
        }

        .yes-btn {
            background-color: #2f41ff;
            color: #fff;
            border: 1px solid #fff;
        }

        .form-txt {
            text-align: center;
            margin-top: 15px;
            color: #fff;
        }

        .form-txt a {
            color: #fff;
            text-decoration: underline;
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
                                        <span class="rounded-circle">
                                            <img src="{{ checkFileExist(config('general_settings.SITE_LOGO')) }}"
                                                alt="logo" height="120" width="220">
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="p-2">
                                <form id="otp_form" action="{{ route('user.verify.otp.post') }}" method="post"
                                    class="auth-form">
                                    @csrf

                                    <div class="otp-form text-center">
                                        <div class="form-group">
                                            <div class="otp_box">
                                                @for ($i = 0; $i < 6; $i++)
                                                    <input type="text" name="otp_no[]" maxlength="1" pattern="\d*"
                                                        inputmode="numeric" placeholder="-" required>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-btn-grp">
                                        <button type="submit" class="no-btn comn-btn">Confirm Code</button>
                                        <button type="button" id="resend-btn" class="yes-btn comn-btn"
                                            onclick="resendOtp()">Resend Code</button>
                                    </div>

                                    <p class="form-txt"><a href="{{ route('user.login') }}">Back to Login</a></p>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-center text-white">
                        <p>©
                            <script>
                                document.write(new Date().getFullYear())
                            </script>
                            <i class="mdi mdi-heart text-danger"></i>
                            by <span class="text-primary">{{ config('general_settings.SITE_NAME') }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <script>
        // ✅ Auto-focus next input
        document.querySelectorAll('.otp_box input').forEach((input, index, inputs) => {
            input.addEventListener('input', e => {
                e.target.value = e.target.value.replace(/[^0-9]/g, ''); // only digits
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        // ✅ Resend OTP AJAX
        function resendOtp() {
            $('#resend-btn').prop('disabled', true).text('Sending...');

            $.ajax({
                url: '{{ route('user.resend.otp') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success('OTP resent successfully!');
                    $('#resend-btn').prop('disabled', false).text('Resend Code');
                },
                error: function(xhr) {
                    toastr.error('Failed to resend OTP. Please try again.');
                    $('#resend-btn').prop('disabled', false).text('Resend Code');
                }
            });
        }
    </script>
@endsection
