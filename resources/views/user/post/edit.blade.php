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
                            <form id="main_form" action="{{ route('user.post.update', $data->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                {{-- Title --}}
                                {{-- <div class="form-group row mb-3 align-items-center">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Title') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="title" name="title"
                                            placeholder="please enter post title" value="{{ $data->title ?? old('title') }}">
                                    </div>
                                </div> --}}

                                {{-- Description --}}
                                <div class="form-group row mb-3 align-items-center">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Description') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="description" name="description" placeholder="please enter description">{{ $data->description ?? old('description') }}</textarea>
                                    </div>
                                </div>

                                {{-- City --}}
                                <div class="form-group row mb-3 align-items-center">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Location') }}
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="city" name="city"
                                            placeholder="please enter location" value="{{ $data->city ?? old('city') }}">
                                    </div>
                                </div>

                                {{-- Upload New Files --}}
                                <div class="form-group row mb-3 align-items-start">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Post Images / Videos') }}
                                    </label>
                                    <div class="col-sm-9">

                                        {{-- Dynamic New File Inputs --}}
                                        <div id="file-inputs">
                                            <div class="d-flex mb-2">
                                                <input type="file" accept="image/*,video/*" name="post_images[]" class="form-control">
                                                <button type="button" class="btn btn-danger btn-sm ms-2 remove-input" style="display:none;">✕</button>
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <button type="button" class="btn btn-secondary btn-sm" id="add-input">+ Add More</button>
                                        </div>

                                        {{-- Existing Files --}}
                                        <div class="mt-3 d-flex flex-wrap gap-3">
                                            @foreach($data->postImages as $img)
                                                <div class="position-relative border rounded" style="width:80px; height:80px; position:relative;">
                                                    @if($img->type === 'image')
                                                        <img src="{{ ($img->image_path) }}" 
                                                            class="img-fluid rounded" style="width:100%; height:100%; object-fit:cover;">
                                                    @else
                                                        <video width="100%" height="100%" class="rounded" controls>
                                                            <source src="{{ ($img->image_path) }}" type="video/mp4">
                                                        </video>
                                                    @endif

                                                    {{-- Delete Old File --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger p-0 px-1 position-absolute top-0 end-0"
                                                        onclick="deleteImage({{ $img->id }})">✕</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <div class="row justify-content-end">
                                    <div class="col-sm-9">
                                        <div>
                                            <button id="submit-btn" class="btn btn-primary waves-effect waves-light" type="submit">
                                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
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
    
    <script>
        document.getElementById('add-input').addEventListener('click', function() {
            let container = document.getElementById('file-inputs');
            let div = document.createElement('div');
            div.classList.add('d-flex', 'mb-2');
            div.innerHTML = `
                <input type="file" accept="image/*,video/*" name="post_images[]" class="form-control">
                <button type="button" class="btn btn-danger btn-sm ms-2 remove-input">✕</button>
            `;
            container.appendChild(div);

            div.querySelector('.remove-input').addEventListener('click', function() {
                div.remove();
            });
        });

        // Delete Image/Video via fetch
        function deleteImage(id) {
            if(confirm("Are you sure you want to delete this file?")) {
                fetch("{{ route('user.post.image.delete', '') }}/" + id, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                }).then(res => {
                    if(res.ok) location.reload();
                });
            }
        }
        </script>
@endsection
