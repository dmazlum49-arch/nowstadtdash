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
                            <form id="main_form" action="{{ route('admin.change-password.update') }}" method="POST">
                                @csrf
                                <div class="row mb-4">
                                    <label for="opassword" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Old Password</label>
                                    <div class="col-sm-9 auth-pass-inputgroup">
                                        <input type="password" class="form-control" id="opassword" name="opassword" placeholder="Please enter old password.">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label for="npassword" class="col-sm-3 col-form-label"><span class="text-danger">*</span>New Password</label>
                                    <div class="col-sm-9 auth-npass-inputgroup">
                                        <input type="password" class="form-control" id="npassword" name="npassword" placeholder="Please enter new password.">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label for="cpassword" class="col-sm-3 col-form-label"><span class="text-danger">*</span>Confirm Password</label>
                                    <div class="col-sm-9 auth-cpass-inputgroup">
                                        <input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="Please enter confirm password.">
                                    </div>
                                </div>
                                <div class="row">
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
    <script>
        $(document).ready(function () {
            jQuery.validator.addMethod("notEqual", function(value, element, param) {
                return this.optional(element) || value != $('#opassword').val();
            }, "Please specify a different (non-default) value");
            $("#main_form").validate({
                rules: {
                    opassword: { required: true},
                    npassword: { required: true, minlength: 6, notEqual: '#opassword',},
                    cpassword: { required: true, equalTo: "#npassword",},
                },
                messages: {
                    opassword: {
                        required: "{{__('admin.errors.opassword.required')}}",
                    },
                    npassword: {
                        required: "{{__('admin.errors.npassword.required')}}",
                        minlength: "{{__('admin.errors.npassword.minlength')}}",
                        notEqual: "{{__('admin.errors.npassword.notEqual')}}",
                    },
                    cpassword: {
                        required: "{{__('admin.errors.cpassword.required')}}",
                        equalTo: "{{__('admin.errors.cpassword.equal_to')}}",
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
                submitHandler: function (form) {
                    // Show loader
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true); // Disable the button to prevent multiple clicks
                    submitBtn.find(".spinner-border").removeClass("d-none"); // Show the spinner
                    submitBtn.contents().last().replaceWith(""); // Update button text
                    submitBtn.contents().last().replaceWith(" <i class='mdi mdi-spin me-1'></i> Sending..."); // Update button text
                    form.submit();
                }
            });
        });
    </script>
@endsection
