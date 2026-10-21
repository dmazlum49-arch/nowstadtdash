@extends('layouts.admin.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="main_form" action="{{ route('user.sub-business-users.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @include('user.sub-business-users.form')
                                <div class="row justify-content-end">
                                    <div class="col-sm-9">
                                        <button id="submit-btn" class="btn btn-primary waves-effect waves-light" type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                            Submit
                                        </button>
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
                    name: { required: true },
                    email: { required: true, email: true },
                    password: { required: true, minlength: 6 },
                    password_confirmation: { required: true, equalTo: "#password" }
                },
                errorClass: "text-danger",
                errorElement: "small",
                submitHandler: function (form) {
                    const submitBtn = $("#submit-btn");
                    submitBtn.prop("disabled", true);
                    submitBtn.find(".spinner-border").removeClass("d-none");
                    form.submit();
                }
            });
        });
    </script>
@endsection
