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
                            <form id="main_form" action="{{ route('admin.home-banner.update', $data->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <ul class="nav nav-tabs justify-content-center mb-3" style="font-size: 16px"
                                    id="languageTabs" role="tablist">
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

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Image') }}
                                    </label>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <input type="file" accept="image/*" id="profile_image"
                                            class="form-control profile_image" name="profile_image">
                                        <input type="hidden" name="image_base64">
                                    </div>

                                    <div class="col-lg-2 col-md-3 col-sm-12 text-center">
                                        <div id="hide_old_image">
                                            {!! get_fancy_box_html($data->image) !!}
                                        </div>
                                        <img src="" style="display: none;"
                                            class="img_75 show-image rounded border" />
                                    </div>
                                </div>

                                <!-- Link -->
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="link" class="col-form-label col-lg-3 col-sm-12">
                                        {{ __('Link') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="text" id="link" class="form-control" name="link"
                                            placeholder="Enter Link" value="{{ old('link', $data->link) }}">
                                    </div>
                                </div>

                                <div class="tab-content">
                                    @foreach (getSupportedLanguages() as $code => $language)
                                        <div class="tab-pane fade @if ($loop->first) show active @endif"
                                            id="tab-{{ $code }}" role="tabpanel">

                                            <!-- Title -->
                                            <div class="mb-4 row">
                                                <label for="title_{{ $code }}" class="col-sm-3 col-form-label">
                                                    Title ({{ $language }})
                                                </label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control"
                                                        id="title_{{ $code }}" name="title[{{ $code }}]"
                                                        value="{{ old('title.' . $code, $translations[$code]['title'] ?? '') }}"
                                                        placeholder="Enter Title in {{ $language }}">
                                                </div>
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-4 row">
                                                <label for="description_{{ $code }}"
                                                    class="col-sm-3 col-form-label">
                                                    Description ({{ $language }})
                                                </label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control" id="description_{{ $code }}" name="description[{{ $code }}]"
                                                        placeholder="Enter Description in {{ $language }}">{{ old('description.' . $code, $translations[$code]['description'] ?? '') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
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
                                            <h5 class="modal-title" id="modalLabel">Banner Image</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
    <script>
        $(document).ready(function() {
            const rules = {
                image: {
                    required: false,
                },
                // link: {
                //     required: true,
                //     url: true,
                // }
            };

            const messages = {
                image: {
                    required: "Please upload an image.",
                },
                // link: {
                //     required: "Please enter a link.",
                //     url: "Please enter a valid URL."
                // }
            };

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
                    submitBtn.contents().last()[0].textContent = " Sending...";
                    form.submit();
                }
            });
        });
    </script>
@endsection
