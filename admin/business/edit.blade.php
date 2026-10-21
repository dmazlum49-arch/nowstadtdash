@extends('layouts.admin.app')

@section('head_css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.css" />
    <style>
        .modal-lg {
            max-width: 1000px !important;
        }

        .preview {
            text-align: center;
            overflow: hidden;
            width: 160px;
            height: 160px;
            margin: 10px;
            border: 1px solid red;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .img_75 {
            height: 50px;
            width: 50px;
            object-fit: cover;
            border: 1px solid #ddd;
            padding: 2px;
        }


        .business-timings-table {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .business-timings-table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        .business-timings-table td {
            padding: 10px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .business-timings-table tr:hover {
            background-color: #f1f3f5;
        }

        .business-timings-table .is-open {
            width: 15px;
            height: 15px;
            cursor: pointer;
            accent-color: #007bff;
        }

        .business-timings-table .form-control {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease-in-out;
        }

        .business-timings-table .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            outline: none;
        }

        .business-timings-table .form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
        }

        .text-danger {
            color: #dc3545;
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
                            <form id="main_form" action="{{ route('admin.business.update', $business->id) }}"
                                enctype="multipart/form-data" method="POST">
                                @csrf
                                @method('PUT')
                                <!-- Image Upload -->
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Logo') }}
                                    </label>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <input type="file" accept="image/*" id="profile_image"
                                            class="form-control crop-input" name="logo" data-target-input="image_base64" data-target-preview="logo-preview">
                                        <input type="hidden" name="image_base64">
                                    </div>

                                    <div class="col-lg-2 col-md-3 col-sm-12 text-center">
                                        <div id="hide_old_image">
                                            {!! get_fancy_box_html($business->logo) !!}
                                        </div>
                                        <img src="" style="display: none;"
                                            class="img_75 logo-preview rounded border" />
                                    </div>
                                </div>
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="banner_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger"></span>{{ __('Banner Image') }}
                                    </label>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <input type="file" accept="image/*" id="banner_image"
                                            class="form-control crop-input" name="banner_image" data-target-input="banner_base64" data-target-preview="banner-preview">
                                        <input type="hidden" name="banner_base64">
                                    </div>

                                    <div class="col-lg-2 col-md-3 col-sm-12 text-center">
                                        <div id="hide_old_banner">
                                            @if($business->banner_image)
                                                {!! get_fancy_box_html($business->banner_image) !!}
                                            @endif
                                        </div>
                                        <img src="" style="display: none;"
                                            class="img_75 banner-preview rounded border" />
                                    </div>
                                </div>
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Business Name') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="business_name" name="business_name"
                                            placeholder="please enter business name" value="{{ $business->business_name }}">
                                    </div>
                                </div>
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Select Category') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <select class="form-control" id="business_category_id" name="business_category_id">
                                            <option value="">{{ __('Select Category') }}</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ $business->business_category_id == $category->id ? 'selected' : '' }}>
                                                    {{ $category->business_category }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Location') }}
                                    </label>

                                    <div class="col-sm-9">
                                        {{-- <input type="text" class="form-control" id="city" name="city"
                                            placeholder="please enter city name" value="{{ $business->city }}"> --}}
                                            <input type="text" class="form-control" id="city" name="city"
                                            placeholder="Enter location" value="{{ $business->city }}">
                                        <input type="hidden" name="latitude" id="latitude">
                                        <input type="hidden" name="longitude" id="longitude">
                                    </div>
                                </div>
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger"></span>{{ __('Business website link ') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="website" name="website"
                                            placeholder="please enter business website link"
                                            value="{{ $business->website }}">
                                    </div>
                                </div>
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('About') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="about_business" name="about_business" placeholder="please enter about">{{ $business->about_business }}</textarea>
                                    </div>
                                </div>

                                <div class="form-group row mb-3">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span> {{ __('Business Timings') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <table class="table table-bordered business-timings-table">
                                            <thead>
                                                <tr>
                                                    <th>Day</th>
                                                    <th>Open?</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $days = [
                                                        'monday',
                                                        'tuesday',
                                                        'wednesday',
                                                        'thursday',
                                                        'friday',
                                                        'saturday',
                                                        'sunday',
                                                    ];
                                                @endphp

                                                @foreach ($days as $day)
                                                    @php
                                                        $existing = isset($business)
                                                            ? $business->timings->where('day', $day)->first()
                                                            : null;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ ucfirst($day) }}</td>
                                                        <td>
                                                            <input type="checkbox" class="is-open"
                                                                data-day="{{ $day }}"
                                                                {{ $existing && $existing->is_open ? 'checked' : '' }}>
                                                        </td>
                                                        <td>
                                                            <input type="time" class="form-control start-time"
                                                                data-day="{{ $day }}"
                                                                value="{{ $existing ? $existing->start_time : '' }}"
                                                                {{ $existing && $existing->is_open ? '' : 'disabled' }}>
                                                        </td>
                                                        <td>
                                                            <input type="time" class="form-control end-time"
                                                                data-day="{{ $day }}"
                                                                value="{{ $existing ? $existing->end_time : '' }}"
                                                                {{ $existing && $existing->is_open ? '' : 'disabled' }}>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <input type="hidden" name="business_timing" id="business_timing">
                                    </div>
                                </div>
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
                            <div class="modal fade" id="modal" tabindex="-1" role="dialog"
                                aria-labelledby="modalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalLabel">Business Image</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">×</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="img-container">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <img id="image"
                                                            src="https://avatars0.githubusercontent.com/u/3456749">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="preview"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Cancel</button>
                                            <button type="button" class="btn btn-primary" id="crop">Crop</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAWwD_yZeZPrnExg5TUrMLLvsBPR5UhKZ4&libraries=places"></script>

    <script>
        function initAutocomplete() {
                const input = document.getElementById('city');
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
    </script>

    {{-- croping image --}}
    <script>
        var $modal = $('#modal');
        var image = document.getElementById('image');
        var cropper;
        var currentTargetInput;
        var currentTargetPreview;
        var currentHideOld;

        $("body").on("change", ".crop-input", function(e) {
            var files = e.target.files;

             // Store targets for the current crop session;
             var input = $(this);
            currentTargetInput = $("input[name='" + input.data('target-input') + "']");
            currentTargetPreview = $("." + input.data('target-preview'));
            
            // Logic to hide old image specific to the input being changed
            if (input.attr('id') === 'profile_image') {
                currentHideOld = $('#hide_old_image');
            } else if (input.attr('id') === 'banner_image') {
                currentHideOld = $('#hide_old_banner');
            }

            var done = function(url) {
                image.src = url;
                $modal.modal('show');
            };

            // Don't hide immediately, wait for crop
            // if(currentHideOld) currentHideOld.hide(); 

            var reader;
            var file;
            var url;

            if (files && files.length > 0) {
                file = files[0];

                if (URL) {
                    done(URL.createObjectURL(file));
                } else if (FileReader) {
                    reader = new FileReader();
                    reader.onload = function(e) {
                        done(reader.result);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        $modal.on('shown.bs.modal', function() {
            cropper = new Cropper(image, {
                aspectRatio: NaN,
                viewMode: 1,
                preview: '.preview'
            });
        }).on('hidden.bs.modal', function() {
            cropper.destroy();
            cropper = null;
        });

        $("#crop").click(function() {
            canvas = cropper.getCroppedCanvas({
                width: 500,
                height: 500,
            });

            canvas.toBlob(function(blob) {
                var url = URL.createObjectURL(blob);
                var reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = function() {
                    var base64data = reader.result;
                    
                    if (currentTargetInput) {
                        currentTargetInput.val(base64data);
                    }
                    
                    if (currentTargetPreview) {
                        currentTargetPreview.show();
                        currentTargetPreview.attr("src", base64data);
                    }
                    
                    if (currentHideOld) {
                        currentHideOld.hide();
                    }

                    $("#modal").modal('toggle');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle checkbox toggle to enable/disable time inputs
            document.querySelectorAll('.is-open').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const startTime = row.querySelector('.start-time');
                    const endTime = row.querySelector('.end-time');
                    startTime.disabled = !this.checked;
                    endTime.disabled = !this.checked;
                });
            });

            // Handle form submission to collect timings
            document.querySelector('form').addEventListener('submit', function(e) {
                let timings = [];
                document.querySelectorAll('table tbody tr').forEach(function(row) {
                    let day = row.querySelector('.is-open').dataset.day;
                    let isOpen = row.querySelector('.is-open').checked ? 1 : 0;
                    let startTime = row.querySelector('.start-time').value || "00:00";
                    let endTime = row.querySelector('.end-time').value || "00:00";

                    timings.push({
                        day: day,
                        is_open: isOpen,
                        start_time: startTime,
                        end_time: endTime
                    });
                });

                document.getElementById('business_timing').value = JSON.stringify(timings);
            });
        });
    </script>
@endsection