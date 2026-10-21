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
                            <form id="main_form" action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data" >
                                @csrf
                                <div class="row mb-4">
                                    <label for="profile_image" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Profile Image</label>
                                    <div class="col-sm-9">
                                        <input class="form-control" type="file" accept="image/*" id="profile_image" name="profile_image">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label for="name" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="name" name="name" value="{{$user->name}}">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label for="username" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Username</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="username" name="username" value="{{$user->username}}">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label for="email" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="{{$user->email}}">
                                    </div>
                                </div>
                                <div class="row justify-content-end">
                                    <div class="col-sm-9">
                                        <div>
                                            <button id="submit-btn" class="btn btn-primary waves-effect waves-light" type="submit">
                                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                                Save Changes
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
    <script>
        $(document).ready(function () {
            $("#main_form").validate({
                rules: {
                    name: { required: true},
                    email: {
                        required: true,
                        email: true,
                        remote: {
                            type: 'get',
                            url: "{{route('user.availability-checker')}}",
                            data: {
                                'type': "email",
                                'val': function() {
                                    return $('#email').val();
                                }
                            },
                        },
                    },
                    username: {
                        required: true,
                        remote: {
                            type: 'get',
                            url: "{{route('user.availability-checker')}}",
                            data: {
                                'type': "username",
                                'val': function() {
                                    return $('#username').val();
                                }
                            },
                        },
                    },
                },
                messages: {
                    email: {
                        required: "{{__('admin.errors.email.required')}}",
                        remote: "{{__('admin.errors.email.remote')}}",
                    },
                    name: {
                        required: "{{__('admin.errors.name.required')}}",
                    },
                    username: {
                        required: "{{__('admin.errors.username.required')}}",
                        remote: "{{__('admin.errors.username.remote')}}",
                    }
                },
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
