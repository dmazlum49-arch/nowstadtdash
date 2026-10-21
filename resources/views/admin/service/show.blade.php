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
                            <h5 class="font-size-14">Business Name:</h5>
                            <p class="text-muted">{{ $data->business->business_name }}</p>
                            <hr>
                            <h5 class="font-size-14">User Name:</h5>
                            <p class="text-muted">{{ $data->user->name }}</p>
                            <hr>
                            <h5 class="font-size-14">Title:</h5>
                            <p class="text-muted">{{ $data->title }}</p>
                            <hr>
                            <h5 class="font-size-14">Image:</h5>
                            @if ($data->service_image == null)
                                <p class="text-muted">No Image</p>
                            @else
                                <a data-fancybox="gallery" href="{{ url($data->service_image) }}" target="_blank">
                                    <img src="{{ url($data->service_image) }}" alt="Service Image"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                </a>
                            
                            @endif
                            <hr>
                            <h5 class="font-size-15">Price:</h5>
                            <p class="text-muted">{{ $data->price }}</p>
                            <hr>
                            <h5 class="font-size-15">Description:</h5>
                            <p class="text-muted">{{ $data->description }}</p>
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
