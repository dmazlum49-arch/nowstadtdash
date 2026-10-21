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
                            <form id="main_form" action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row mb-3">
                                    <label for="name" class="col-sm-3 col-form-label">Role Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="name" class="form-control" id="name" placeholder="Enter Role Name" value="{{ $role->name }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Permissions</label>
                                    <div class="col-sm-9">
                                        <div class="row">
                                            @foreach($permissions as $permission)
                                                <div class="col-md-3">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                                        <label class="form-check-input-label" for="perm_{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Sub Admins</label>
                                    <div class="col-sm-9">
                                        <div class="row">
                                            @foreach($subAdmins as $subAdmin)
                                                <div class="col-md-3">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="users[]" value="{{ $subAdmin->id }}" id="user_{{ $subAdmin->id }}" {{ in_array($subAdmin->id, $roleUsers) ? 'checked' : '' }}>
                                                        <label class="form-check-input-label" for="user_{{ $subAdmin->id }}">
                                                            {{ $subAdmin->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="row justify-content-end">
                                    <div class="col-sm-9">
                                        <button id="submit-btn" class="btn btn-primary waves-effect waves-light" type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                            Update
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
                    name: { required: true},
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
