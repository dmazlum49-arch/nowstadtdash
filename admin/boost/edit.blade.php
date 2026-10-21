@extends('layouts.admin.app')

@section('head_css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                            <form id="main_form" action="{{ route('admin.boost.update', $data->id) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <div class="tab-content">

                                    {{-- Business --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            <span class="text-danger">*</span>{{ __('Business') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <select class="form-select abcd" name="business_ids[]" multiple>
                                                <option value="">{{ __('Select Business') }}</option>
                                                @foreach ($businesses as $business)
                                                    <option value="{{ $business->id }}"
                                                        {{ in_array($business->id, (array) old('business_ids', [$data->business_id])) ? 'selected' : '' }}>
                                                        {{ $business->business_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Start Date --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            <span class="text-danger">*</span>{{ __('Start Date') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="start_date" name="start_date"
                                                value="{{ old('start_date', $data->start_date) }}">
                                        </div>
                                    </div>

                                    {{-- Start Time --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            <span class="text-danger">*</span>{{ __('Start Time') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="time" class="form-control" id="start_time" name="start_time"
                                                value="{{ old('start_time', $data->start_time) }}">
                                        </div>
                                    </div>

                                    {{-- End Date --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            <span class="text-danger">*</span>{{ __('End Date') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="end_date" name="end_date"
                                                value="{{ old('end_date', $data->end_date) }}">
                                        </div>
                                    </div>

                                    {{-- End Time --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            <span class="text-danger">*</span>{{ __('End Time') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="time" class="form-control" id="end_time" name="end_time"
                                                value="{{ old('end_time', $data->end_time) }}">
                                        </div>
                                    </div>

                                    {{-- Multiplier --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            <span class="text-danger">*</span>{{ __('Boost Multiplier') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <select class="form-select" id="multiplier" name="multiplier" required>
                                                <option value="2" {{ old('multiplier', $data->multiplier ?? 2) == 2 ? 'selected' : '' }}>2x Nowis</option>
                                                <option value="4" {{ old('multiplier', $data->multiplier ?? 2) == 4 ? 'selected' : '' }}>4x Nowis</option>
                                                <option value="8" {{ old('multiplier', $data->multiplier ?? 2) == 8 ? 'selected' : '' }}>8x Nowis</option>
                                                <option value="16" {{ old('multiplier', $data->multiplier ?? 2) == 16 ? 'selected' : '' }}>16x Nowis</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Label --}}
                                    <div class="form-group row mb-3 align-items-center">
                                        <label class="col-form-label col-lg-3 col-sm-12">
                                            {{ __('Badge / Label') }}
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="label" name="label"
                                                value="{{ old('label', $data->label) }}" placeholder="e.g. 2x Nowis (Leave empty to use multiplier)">
                                        </div>
                                    </div>

                                </div>

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

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!--tinymce js-->
    <script src="{{ url('assets/admin/libs/tinymce/tinymce.min.js') }}"></script>

    <!-- init js -->
    <script src="{{ url('assets/admin/js/pages/form-editor.init.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    {{-- croping image --}}
    
    <script>
        $(document).ready(function() {
            // jQuery Validate
            $("#main_form").validate({
                rules: {
                    'business_ids[]': {
                        required: true
                    },
                    start_date: {
                        required: true
                    },
                    start_time: {
                        required: true
                    },
                    end_date: {
                        required: true
                    },
                    end_time: {
                        required: true
                    },
                    multiplier: {
                        required: true
                    },
                },
                messages: {
                    'business_ids[]': {
                        required: "{{ __('admin.errors.business_ids.required') }}"
                    },
                    start_date: {
                        required: "{{ __('admin.errors.start_date.required') }}"
                    },
                    start_time: {
                        required: "{{ __('admin.errors.start_time.required') }}"
                    },
                    end_date: {
                        required: "{{ __('admin.errors.end_date.required') }}"
                    },
                    end_time: {
                        required: "{{ __('admin.errors.end_time.required') }}"
                    },
                    // points: {
                    //     required: "{{ __('admin.errors.points.required') }}",
                    //     number: "Enter a valid number",
                    //     min: "Points must be at least 1"
                    // },
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
                    if (element.hasClass('abcd')) {
                        error.insertAfter(element.next());
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true);
                    submitBtn.find(".spinner-border").removeClass("d-none");
                    submitBtn.contents().last().replaceWith(" Sending...");
                    form.submit();
                }
            });

            // Select2
            $('.abcd').select2({
                placeholder: "Select Business"
            });
        });
    </script>
@endsection
