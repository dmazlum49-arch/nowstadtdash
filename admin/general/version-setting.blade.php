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
                            <form id="main_form" action="{{ route('admin.site-settings.update') }}" method="POST" enctype="multipart/form-data" >
                                @csrf
                                @foreach($fields as $field)
                                    <div class="row mb-4">
                                        <label for="{{ $field['unique_name'] }}" class="col-sm-3 col-form-label">
                                            @if($field['extra']) <span class="text-danger">*</span> @endif{{ $field['label'] }}
                                        </label>
                                        @switch($field['input_type'])
                                            @case('text')
                                            @case('number')
                                            @case('email')
                                            @case('url')
                                                <div class="col-sm-9">
                                                    <input type="{{ $field['input_type'] }}"
                                                           name="{{ $field['id'] }}"
                                                           id="{{ $field['unique_name'] }}"
                                                           class="{{ $field['class'] }}"
                                                           placeholder="{{ $field['hint'] }}"
                                                           value="{{ $field['value'] }}" pattern="^\d+(\.\d+)*$"
                                                           oninput="validateVersionFormat(this)"
                                                           aria-invalid="false"
                                                        {!! echo_extra_for_site_setting($field['extra']) !!}
                                                    >
                                                </div>
                                                @break

                                            @case('file')
                                                <div class="col-sm-8">
                                                    <input type="file"
                                                           name="{{ $field['id'] }}"
                                                           id="{{ $field['unique_name'] }}"
                                                           class="{{ $field['class'] }}"
                                                        {!! echo_extra_for_site_setting($field['extra']) !!}>
                                                </div>
                                                <div class="col-sm-1"><img src="{{$field['value']}}" class="rounded me-2 avatar-sm"></div>
                                                @break

                                            @case('select')
                                                @if(!empty($field['options']))
                                                    <div class="col-sm-9">
                                                        <select name="{{ $field['id'] }}"
                                                                id="{{ $field['unique_name'] }}"
                                                                class="form-select"
                                                                {!! echo_extra_for_site_setting($field['extra']) !!}
                                                                required>
                                                            @foreach(json_decode($field['options']) as $option)
                                                                <option value="{{ $option->value }}"
                                                                    {{ $option->value == $field['value'] ? 'selected' : '' }}>
                                                                    {{ $option->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endif
                                                @break

                                            @default
                                                <!-- Handle any case where input type is unknown -->
                                                <p>Unsupported input type: {{ $field['input_type'] }}</p>
                                        @endswitch
                                    </div>
                                @endforeach
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
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">History</h4>
                            <table id="version-table" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Version</th>
                                    <th>Type</th>
                                    <th>Is Force</th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <script>
        $(function() {
            $('#version-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.version.index') }}',
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'version', orderable: false, searchable: false },
                    { data: 'type' },
                    { data: 'is_force' },
                ]
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            $("#main_form").validate({
                errorClass: "text-danger",
                errorElement: "small",
                highlight: function (element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function (element) {
                    $(element).removeClass("is-invalid");
                },
                errorPlacement: function (error, element) {
                    error.insertAfter(element);
                },
                submitHandler: function (form) {
                    // Show loader
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true); // Disable the button to prevent multiple clicks
                    submitBtn.find(".spinner-border").removeClass("d-none"); // Show the spinner
                    submitBtn.contents().last().replaceWith("");
                    submitBtn.contents().last().replaceWith(" <i class='mdi mdi-spin me-1'></i> Sending..."); // Update button text

                    form.submit();
                }
            });
        });
    </script>
@endsection
