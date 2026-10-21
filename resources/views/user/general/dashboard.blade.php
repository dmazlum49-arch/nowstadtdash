@extends('layouts.admin.app')

@section('head_css')
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')
            <div class="row">
                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <a class="kt-link" href="{{ route('user.business.index') }}">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <p class="text-muted fw-medium">Businesss</p>
                                                <h4 class="mb-0">{{ $business_count }}</h4>
                                            </div>
                                            <div class="flex-shrink-0 align-self-center">
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                    <span class="avatar-title">
                                                        <i class="fa fa-briefcase font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <a class="kt-link" href="{{ route('user.event.index') }}">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <p class="text-muted fw-medium">Evenets</p>
                                                <h4 class="mb-0">{{ $event_count }}</h4>
                                            </div>
                                            <div class="flex-shrink-0 align-self-center">
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                    <span class="avatar-title">
                                                        <i class="fa fa-calendar font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <a class="kt-link" href="{{ route('user.post.index') }}">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <p class="text-muted fw-medium">Posts</p>
                                                <h4 class="mb-0">{{ $post_count }}</h4>
                                            </div>
                                            <div class="flex-shrink-0 align-self-center ">
                                                <div class="avatar-sm rounded-circle bg-primary mini-stat-icon">
                                                    <span class="avatar-title rounded-circle bg-primary">
                                                        <i class="fa fa-tasks font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
@endsection
