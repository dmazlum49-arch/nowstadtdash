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
                            <h5 class="font-size-14">Business Category:</h5>
                            <p class="text-muted">{{ $data->business_category }}</p>
                            <hr>
                            <hr>
                            <h5 class="font-size-15">Status:</h5>
                            <p class="text-muted">{!! get_badge_html($data->status) !!}</p>
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
