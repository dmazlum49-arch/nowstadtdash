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
                            <ul class="nav nav-tabs justify-content-center mb-3" style="font-size: 16px" id="languageTabs" role="tablist">
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
                            <form id="main_form" action="{{ route('admin.event-category.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                {{-- <div class="row mb-4">
                                    <label for="service_name" class="col-sm-3 col-form-label"><span
                                            class="text-danger">*</span>Event Category</label>
                                    <div class="col-sm-9">
                                        <input class="form-control" type="text" id="event_category" name="event_category"
                                            value="" placeholder="Please enter Event Category">
                                    </div>
                                </div> --}}
                                <div class="tab-content">
                                    @foreach (getSupportedLanguages() as $code => $language)
                                        <div class="tab-pane fade @if ($loop->first) show active @endif"
                                            id="tab-{{ $code }}" role="tabpanel">
                                            <div class="mb-4 row">
                                                <label for="event_category_{{ $code }}"
                                                    class="col-sm-3 col-form-label">
                                                    <span class="text-danger">*</span>Event Category
                                                    ({{ $language }})
                                                </label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control"
                                                        id="event_category_{{ $code }}"
                                                        name="event_category[{{ $code }}]"
                                                        placeholder="Enter Event Category in {{ $language }}">
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
            // Dynamically build rules/messages based on supported languages
            const rules = {};
            const messages = {};

            @foreach (getSupportedLanguages() as $langCode => $langName)
                rules["event_category[{{ $langCode }}]"] = {
                    required: true,
                    remote: {
                            type: 'get',
                            url: "{{ route('admin.event-category.check-name') }}",
                            data: {
                                val: function() {
                                    return $('#event_category_{{ $langCode }}').val();
                                }
                            },
                        },
                };
                messages["event_category[{{ $langCode }}]"] = {
                    required: "{{ __('admin.errors.event_category.required') }} ({{ $langName }})",
                    remote: "{{ __('admin.errors.event_category.remote') }} ({{ $langName }})"  
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
