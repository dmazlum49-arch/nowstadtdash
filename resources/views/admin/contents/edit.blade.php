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
                            <form id="main_form" action="{{ route('admin.contents.update', $data->id) }}" method="POST"
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

                                <div class="tab-content">
                                    @foreach (getSupportedLanguages() as $code => $language)
                                        <div class="tab-pane fade @if ($loop->first) show active @endif"
                                            id="tab-{{ $code }}" role="tabpanel">
                                            <div class="row mb-4">
                                                <label for="title_{{ $code }}" class="col-sm-3 col-form-label">
                                                    <span class="text-danger">*</span>Title ({{ strtoupper($code) }})
                                                </label>
                                                <div class="col-sm-9">
                                                    <input class="form-control" type="text"
                                                        id="title_{{ $code }}" name="title[{{ $code }}]"
                                                        value="{{ $translations[$code]->title ?? '' }}"
                                                        placeholder="Please enter title in {{ $language }}">
                                                </div>
                                            </div>

                                            <div class="row mb-4">
                                                <label for="content_{{ $code }}" class="col-sm-3 col-form-label">
                                                    <span class="text-danger">*</span>Details ({{ strtoupper($code) }})
                                                </label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control tinymce-editor" id="content_{{ $code }}" name="details[{{ $code }}]"
                                                        rows="4" placeholder="Enter content in {{ $language }}">{{ $translations[$code]->content ?? '' }}</textarea>
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
    <script>
        $(document).ready(function() {

            $("#main_form").validate({
                ignore: [],
                rules: {
                    @foreach (getSupportedLanguages() as $code => $lang)
                        "title[{{ $code }}]": {
                            required: true
                        },
                        "details[{{ $code }}]": {
                            required: function(textarea) {
                                return tinymce.get('content_{{ $code }}')?.getContent({
                                    format: 'text'
                                }).trim() === "";
                            }
                        },
                    @endforeach
                },
                messages: {
                    @foreach (getSupportedLanguages() as $code => $lang)
                        "title[{{ $code }}]": {
                            required: "{{ __('admin.errors.title.required') }} ({{ strtoupper($code) }})"
                        },
                        "details[{{ $code }}]": {
                            required: "{{ __('admin.errors.details.required') }} ({{ strtoupper($code) }})"
                        },
                    @endforeach
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
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true); // Disable the button to prevent multiple clicks
                    submitBtn.find(".spinner-border").removeClass("d-none"); // Show the spinner
                    submitBtn.contents().last().replaceWith("");
                    submitBtn.contents().last().replaceWith(
                        " <i class='mdi mdi-spin me-1'></i> Sending..."); // Update button text
                        tinymce.triggerSave();

                    form.submit();
                }
            });
        });
    </script>
@endsection
