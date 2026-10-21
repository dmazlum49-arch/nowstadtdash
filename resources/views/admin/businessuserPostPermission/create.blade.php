@extends('layouts.admin.app')

@section('head_css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.css" />
    <style>
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
                            <form id="main_form" action="{{ route('admin.business-post-permissions.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger">*</span>{{ __('Select Business User') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <select class="form-control" id="user_id" name="user_id">
                                            <option value="">{{ __('Select Business User') }}</option>
                                            @foreach ($BusinessUsers as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                               
                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger"></span>{{ __('Max Posts') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="max_posts" name="max_posts"
                                            placeholder="please enter max posts">
                                    </div>
                                </div>

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger"></span>{{ __('Max Events') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="max_events" name="max_events"
                                            placeholder="please enter max events">
                                    </div>
                                </div>

                                <div class="form-group row mb-3 align-items-center">
                                    <label for="profile_image" class="col-form-label col-lg-3 col-sm-12">
                                        <span class="text-danger"></span>{{ __('Max Push Notifications') }}
                                    </label>

                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="max_push_notifications" name="max_push_notifications"
                                            placeholder="please enter max push notifications">
                                    </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

@endsection
