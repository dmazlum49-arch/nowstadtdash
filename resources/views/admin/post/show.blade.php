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
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Title:</h5>
                                    <p class="text-muted">
                                        {{ $data->title ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">City:</h5>
                                    <p class="text-muted">{{ $data->city ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="font-size-15">Description:</h5>
                                    <p class="text-muted">{{ $data->description }}</p>
                                </div>
                            </div>
                            <hr>
                                <div class="col-6">
                                    <h4 class="card-title">Gallery:</h4>
                                    @if (!empty($data->postImages) && count($data->postImages) > 0)
                                        <div class="d-flex gap-2 flex-wrap">
                                            @foreach ($data->postImages as $img)
                                            @if($img->type === 'image')
                                                <a data-fancybox="gallery" href="{{ ($img->image_path) }}"
                                                    target="_blank">
                                                    <img src="{{ ($img->image_path) }}" alt="Business Image"
                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                </a>
                                                @else
                                                <a data-fancybox="gallery" href="{{ ($img->image_path) }}"
                                                    target="_blank">
                                                    <video width="50px" height="50px" style="border-radius: 5px;">
                                                        <source src="{{ ($img->image_path) }}" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <h6 class="card-subtitle font-14 text-muted">-</h6>
                                    @endif
                                </div>

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
