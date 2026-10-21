@extends('layouts.admin.app')

@section('head_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="main_form" action="{{ route('user.notification.store') }}" method="POST">
                                @csrf

                                <!-- Title -->
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="title" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>Title
                                    </label>
                                    <div class="col-sm-9">
                                        <input type="text" id="title" class="form-control" name="title"
                                            placeholder="Enter Title" required>
                                    </div>
                                </div>

                                <!-- Message -->
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="message" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>Message
                                    </label>
                                    <div class="col-sm-9">
                                        <textarea id="message" class="form-control" name="message" rows="3" placeholder="Enter Message" required></textarea>
                                    </div>
                                </div>

                                <!-- Notification Type -->
                                <div class="form-group row mb-3 align-items-center">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>Notification Type
                                    </label>
                                    <div class="col-sm-9">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="type" id="all_users"
                                                value="all" checked>
                                            <label class="form-check-label" for="all_users">All Users</label>
                                        </div>
                                        {{-- <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="type"
                                                id="selected_users" value="selected">
                                            <label class="form-check-label" for="selected_users">Selected Users</label>
                                        </div> --}}
                                    </div>
                                </div>

                                <!-- Select Users -->
                                <div class="form-group row mb-3 align-items-center d-none" id="user_select_box">
                                    <label class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>Select Users
                                    </label>
                                    <div class="col-sm-9">
                                        <select name="user_ids[]" class="form-control abcd" multiple id="user_ids"
                                            style="width: 100%">
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row justify-content-end">
                                    <div class="col-sm-9">
                                        <button id="submit-btn" class="btn btn-primary" type="submit">Submit</button>
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
    {{--
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <!--tinymce js-->
    <script src="{{ url('assets/admin/libs/tinymce/tinymce.min.js') }}"></script>

    <!-- init js -->
    <script src="{{ url('assets/admin/js/pages/form-editor.init.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allRadio = document.getElementById('all_users');
            const selectedRadio = document.getElementById('selected_users');
            const userSelectBox = document.getElementById('user_select_box');

            function toggleUserBox() {
                if (selectedRadio.checked) {
                    userSelectBox.classList.remove('d-none');
                } else {
                    userSelectBox.classList.add('d-none');
                }
            }

            allRadio.addEventListener('change', toggleUserBox);
            selectedRadio.addEventListener('change', toggleUserBox);
        });
    </script>

    <script>
        $(document).ready(function() {
            $('.abcd').select2({
                placeholder: "Select users",
                allowClear: true,
                width: 'resolve'
            });

            $('input[name="type"]').on('change', function() {
                if ($(this).val() === 'selected') {
                    $('#user_select_box').removeClass('d-none');
                } else {
                    $('#user_select_box').addClass('d-none');
                }
            });

            if ($('input[name="type"]:checked').val() === 'selected') {
                $('#user_select_box').removeClass('d-none');
            }
        });
    </script>


    <script>
        $(document).ready(function() {

            function toggleUserBox() {
                const type = $("input[name='type']:checked").val();
                if (type === 'selected') {
                    $('#user_select_box').removeClass('d-none');
                } else {
                    $('#user_select_box').addClass('d-none');
                }
            }

            $("input[name='type']").on('change', toggleUserBox);
            toggleUserBox();

            $("#main_form").validate({
                ignore: [],
                rules: {
                    title: {
                        required: true
                    },
                    message: {
                        required: true
                    },
                    'user_ids[]': {
                        required: function() {
                            return $("input[name='type']:checked").val() === "selected";
                        }
                    }
                },
                messages: {
                    title: {
                        required: "Please enter a Title."
                    },
                    message: {
                        required: "Please enter a Message."
                    },
                    'user_ids[]': {
                        required: "Please select at least one user."
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
                    if (element.hasClass("select2-hidden-accessible")) {
                        error.insertAfter(element.next('span.select2'));
                    } else {
                        error.insertAfter(element);
                    }
                },

                submitHandler: function(form) {
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true);
                    submitBtn.find(".spinner-border").removeClass("d-none");
                    submitBtn.contents().last()[0].textContent = " Submit...";
                    form.submit();
                }
            });
        });
    </script>
@endsection
