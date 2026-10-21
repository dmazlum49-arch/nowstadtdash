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
                                    <h5 class="font-size-15">Nowis Points:</h5>
                                    <p class="text-muted">{{ $data->nowis_points ?? 'N/A' }}</p>
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
                                    <h5 class="font-size-15">Logo:</h5>
                                    @if (!empty($data->logo))
                                        <a data-fancybox="gallery" href="{{ url($data->logo) }}"
                                            target="_blank">
                                            <img src="{{ url($data->logo) }}" alt="Business Image"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        </a>
                                    @else
                                        <p class="text-muted">No Logo</p>
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
