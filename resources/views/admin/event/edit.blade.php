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
                            <form id="main_form" action="{{ route('admin.my-event.update', $event->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Select Category') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <select class="form-control" id="event_category_id" name="event_category_id">
                                            <option value="">{{ __('Select Category') }}</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" {{ $event->event_category_id == $category->id ? 'selected' : '' }}>{{ $category->event_category }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                 <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Event Name') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="event_name" name="event_name"
                                            placeholder="please enter event name" value="{{ $event->event_name }}">
                                    </div>
                                </div>

                                {{-- <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Event Type') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <select class="form-control" id="event_type" name="event_type">
                                            <option value="">{{ __('Select Event Type') }}</option>
                                            <option value="public event" {{ $event->event_type == 'public event' ? 'selected' : '' }}>{{ __('Public') }}</option>
                                            <option value="private event" {{ $event->event_type == 'private event' ? 'selected' : '' }}>{{ __('Private') }}</option>
                                        </select>
                                    </div>
                                </div> --}}

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('About') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="about_event" name="about_event" placeholder="please enter about">{{ $event->about_event }}</textarea>
                                    </div>
                                </div>

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Location') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="location" name="location"
                                            placeholder="please enter location" value="{{ $event->location }}">
                                    </div>
                                </div>
                                
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="start_date" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Start Date') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $event->start_date }}">
                                    </div>
                                </div>

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="start_time" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Start Time') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="time" class="form-control" id="start_time" name="start_time" value="{{ $event->start_time }}">
                                    </div>
                                </div>

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="end_date" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('End Date') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $event->end_date }}">
                                    </div>
                                </div>

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="end_time" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('End Time') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="time" class="form-control" id="end_time" name="end_time" value="{{ $event->end_time }}">
                                    </div>
                                </div>

                                <!-- Image Upload -->
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Banner Image') }}
                                    </label>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <input type="file" accept="image/*" id="profile_image"
                                            class="form-control profile_image" name="banner_image">
                                        <input type="hidden" name="image_base64">
                                    </div>

                                    <div class="col-lg-2 col-md-3 col-sm-12 text-center">
                                        <div id="hide_old_image">
                                            @if($event->banner_image)
                                            <img src="{{ ($event->banner_image) }}" 
                                                            class="img-fluid rounded" style="width:50px; height:50px; object-fit:cover;">
                                                            @endif
                                        </div>
                                        <img src="" style="display: none;"
                                            class="img_75 show-image rounded border" />
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
                                            <h5 class="modal-title" id="modalLabel">Event Image</h5>
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

    {{-- croping image --}}
    <script>
        var $modal = $('#modal');
        var image = document.getElementById('image');
        var cropper;

        $("body").on("change", ".profile_image", function(e) {
            var files = e.target.files;
            var done = function(url) {
                image.src = url;
                $modal.modal('show');
            };

            $('#hide_old_image').hide();

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
                    $("input[name='image_base64']").val(base64data);
                    $(".show-image").show();
                    $(".show-image").attr("src", base64data);
                    $("#modal").modal('toggle');
                }
            });
        });
    </script>
    
@endsection
