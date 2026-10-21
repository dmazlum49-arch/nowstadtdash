@extends('layouts.admin.app')

@section('head_css')
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <ul class="nav nav-tabs justify-content-center mb-3" style="font-size: 16px" id="languageTabs"
                                role="tablist">
                                @foreach (getSupportedLanguages() as $code => $language)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if ($loop->first) active @endif"
                                            id="{{ $code }}-tab" data-bs-toggle="tab"
                                            data-bs-target="#tab-{{ $code }}" type="button" role="tab">
                                            {{ $language }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <form id="main_form" action="{{ route('admin.city.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="tab-content">
                                    @foreach (getSupportedLanguages() as $code => $language)
                                        <div class="tab-pane fade @if ($loop->first) show active @endif"
                                            id="tab-{{ $code }}" role="tabpanel">
                                            <div class="mb-4 row">
                                                <label for="event_category_{{ $code }}"
                                                    class="col-sm-3 col-form-label">
                                                    <span class="text-danger">*</span>City
                                                    ({{ $language }})
                                                </label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" id="city_{{ $code }}"
                                                        name="city_name[{{ $code }}]"
                                                        placeholder="Enter City in {{ $language }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                                <div class="row justify-content-end">
                                    <div class="col-sm-9">
                                        <div>
                                            <button id="submit-btn" class="btn btn-primary waves-effect waves-light"
                                                type="submit">
                                                <span class="spinner-border spinner-border-sm d-none" role="status"
                                                    aria-hidden="true"></span>
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <!--tinymce js-->
    <script src="{{ url('assets/admin/libs/tinymce/tinymce.min.js') }}"></script>

    <!-- init js -->
    <script src="{{ url('assets/admin/js/pages/form-editor.init.js') }}"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAWwD_yZeZPrnExg5TUrMLLvsBPR5UhKZ4&libraries=places">
    </script>

    <script>
        function initAutocomplete() {
            @foreach (getSupportedLanguages() as $code => $language)
                const input{{ $code }} = document.getElementById('city_{{ $code }}');

                if (input{{ $code }}) {
                    const autocomplete{{ $code }} = new google.maps.places.Autocomplete(
                    input{{ $code }}, {
                        types: ['(cities)'],
                        componentRestrictions: {
                            country: ['at'] // 🇦🇹 Austria only
                        }
                    });

                    autocomplete{{ $code }}.addListener('place_changed', function() {
                        const place = autocomplete{{ $code }}.getPlace();

                        if (place.geometry) {
                            document.getElementById('latitude').value = place.geometry.location.lat();
                            document.getElementById('longitude').value = place.geometry.location.lng();
                        }
                    });
                }
            @endforeach
        }

        if (typeof google !== 'undefined') {
            initAutocomplete();
        }
    </script>

    <script>
        $(document).ready(function() {
            // Dynamically build rules/messages based on supported languages
            const rules = {};
            const messages = {};

            @foreach (getSupportedLanguages() as $langCode => $langName)
                rules["city_name[{{ $langCode }}]"] = {
                    required: true
                };
                messages["city_name[{{ $langCode }}]"] = {
                    required: "{{ __('admin.errors.city_name.required') }} ({{ $langCode }})"
                };
            @endforeach

            $("#main_form").validate({
                rules: rules,
                messages: messages,
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
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true);
                    submitBtn.find(".spinner-border").removeClass("d-none");
                    submitBtn.contents().last().replaceWith(
                        " <i class='mdi mdi-spin me-1'></i> Sending...");
                    form.submit();
                }
            });
        });
    </script>
@endsection
