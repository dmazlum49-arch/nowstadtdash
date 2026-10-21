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
                            <p class="text-muted">{{ $data->title }}</p>
                            <hr>
                            <h5 class="font-size-14">Description:</h5>
                            <p class="text-muted">{{ $data->description }}</p>
                            <hr>
                            <h5 class="font-size-14">Image:</h5>
                            @if (!empty($data->image))
                                <a data-fancybox="gallery" href="{{ $data->image }}" target="_blank">
                                    <img src="{{ $data->image }}" alt="profile Image"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                </a>
                            @else
                                <p class="text-muted">No banner image</p>
                            @endif
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
