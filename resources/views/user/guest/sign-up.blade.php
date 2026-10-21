@extends('layouts.admin.guest.app')

@section('head_css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.8/build/css/intlTelInput.min.css">
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

        /* ============ intlTelInput Full Width Fix ============ */
        .iti {
            width: 100%;
            z-index: 100000;
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
                                <form id="signup-form" class="form-horizontal" action="{{ route('user.signup.post') }}"
                                    method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name"
                                            placeholder="Enter company name">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Enter email">
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Mobile</label>
                                        <div class="w-100 position-relative">
                                            <input type="tel" id="phone" class="form-control w-100" name="mobile"
                                                placeholder="Phone Number">
                                            <input type="hidden" name="country_code" id="country_code">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" class="form-control" name="password"
                                                placeholder="Enter password" aria-label="Password">
                                            <button class="btn btn-light" type="button" id="togglePassword1">
                                                <i class="mdi mdi-eye-outline"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" class="form-control" name="confirm_password"
                                                placeholder="Enter confirm password" aria-label="Confirm Password">
                                            <button class="btn btn-light" type="button" id="togglePassword2">
                                                <i class="mdi mdi-eye-outline"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location"
                                            placeholder="Enter location">
                                        <input type="hidden" name="latitude" id="latitude">
                                        <input type="hidden" name="longitude" id="longitude">
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center">
                                        <button id="signup-btn" class="btn btn-primary waves-effect waves-light"
                                            type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                                aria-hidden="true"></span>
                                            Sign Up
                                        </button>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <a href="{{ route('user.login') }}" class="text-white">
                                            <i class="mdi mdi-lock me-1"></i> Login
                                        </a>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="mt-1 text-center text-white">
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

    {{-- location modal --}}
    <div id="locationModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999999;">
        <div style="background:#fff; margin:15% auto; padding:20px; width:320px; text-align:center; border-radius:12px;">
            <h5 style="font-weight:600;">Soon with you too</h5>
            <p style="color:#555; font-size:14px;">
                Nowstadt is currently available within a 10 km radius of Wiener Neustadt.<br>
                We look forward to reaching your region soon.
            </p>
            <button onclick="$('#locationModal').fadeOut();" class="btn btn-primary w-100 mt-2">Okay</button>
        </div>
    </div>
@endsection

@section('page_script')
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.8/build/js/intlTelInput.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAWwD_yZeZPrnExg5TUrMLLvsBPR5UhKZ4&libraries=places">
    </script>


    <!-- jQuery Validation -->
    <script>
        $(document).ready(function() {
            // ================= intlTelInput Setup =================
            var phoneInput = document.querySelector("#phone");
            var iti = window.intlTelInput(phoneInput, {
                initialCountry: "AT",
                preferredCountries: ['sa', 'us', 'gb', 'in'],
                separateDialCode: true,
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.8/build/js/utils.js"
            });

            $('#country_code').val("+" + iti.getSelectedCountryData().dialCode);
            phoneInput.addEventListener('countrychange', function() {
                $('#country_code').val("+" + iti.getSelectedCountryData().dialCode);
            });

            // ================= Password Toggle =================
            $('#togglePassword1, #togglePassword2').on('click', function() {
                const input = $(this).siblings('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('mdi-eye-outline mdi-eye-off-outline');
            });

            // ================= Google Autocomplete =================
            function initAutocomplete() {
                const input = document.getElementById('location');
                const autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['geocode'],
                    componentRestrictions: {
                        country: ['at']
                    }
                });

                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();
                    if (place.geometry) {
                        $('#latitude').val(place.geometry.location.lat());
                        $('#longitude').val(place.geometry.location.lng());
                    }
                });
            }

            if (typeof google !== 'undefined') {
                initAutocomplete();
            }

            // ================= Helper: Calculate distance =================
            function getDistanceKm(lat1, lon1, lat2, lon2) {
                const R = 6371; // Radius of the earth in km
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a =
                    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) *
                    Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            // ================= jQuery Validation =================
            $("#signup-form").validate({
                rules: {
                    company_name: {
                        required: true
                    },
                    email: {
                        required: true,
                        email: true,
                        remote: {
                            type: 'get',
                            url: "{{ route('user.check.email') }}",
                            data: {
                                field: 'email',
                                value: function() {
                                    return $('#email').val();
                                }
                            },
                        },
                    },
                    mobile: {
                        required: true,
                        minlength: 6
                    },
                    password: {
                        required: true,
                        minlength: 6
                    },
                    confirm_password: {
                        required: true,
                        equalTo: '[name="password"]'
                    },
                    location: {
                        required: true
                    }
                },
                messages: {
                    company_name: {
                        required: "Please enter your company name"
                    },
                    email: {
                        required: "Please enter your email",
                        email: "Please enter a valid email",
                        remote: "This email is already registered"
                    },
                    mobile: {
                        required: "Please enter your phone number",
                        minlength: "Please enter a valid phone number"
                    },
                    password: {
                        required: "Please enter your password",
                        minlength: "Password must be at least 6 characters"
                    },
                    confirm_password: {
                        required: "Please confirm your password",
                        equalTo: "Passwords do not match"
                    },
                    location: {
                        required: "Please enter your location"
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
                    if (element.closest(".auth-pass-inputgroup").length) {
                        error.insertAfter(element.closest(".auth-pass-inputgroup"));
                    } else if (element.attr("id") === "phone") {
                        error.insertAfter(element.closest(".iti"));
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    const lat = parseFloat($('#latitude').val());
                    const lng = parseFloat($('#longitude').val());

                    // Fixed service center location (Wiener Neustadt)
                    const baseLat = 47.8129867;
                    const baseLng = 16.2455634;

                    const distance = getDistanceKm(baseLat, baseLng, lat, lng);

                    if (isNaN(lat) || isNaN(lng)) {
                        alert("Please select a valid location from the suggestions.");
                        return false;
                    }

                    if (distance > 10) {
                        $("#locationModal").fadeIn();
                        return false;
                    }

                    const signupBtn = $("#signup-btn");
                    signupBtn.prop("disabled", true);
                    signupBtn.find(".spinner-border").removeClass("d-none");
                    signupBtn.contents().last().replaceWith("...");
                    form.submit();
                }
            });
        });
    </script>
@endsection
