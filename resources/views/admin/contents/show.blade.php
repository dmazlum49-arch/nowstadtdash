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
                            <h4 class="card-title mb-4">Details</h4>
                            <hr>
                            <h5 class="font-size-14">Title:</h5>
                            <p class="text-muted">{{$data->title}}</p>
                            <hr>
                            <h5 class="font-size-14">Slug:</h5>
                            <p class="text-muted">{{$data->slug}}</p>
                            <hr>
                            <h5 class="font-size-14">Content:</h5>
                            <p class="text-muted">{!! $data->content !!}</p>
                            <hr>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
@endsection
