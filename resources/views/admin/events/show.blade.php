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
                            {{-- <div class="row">
                                <div class="col-6">
                                    <h4 class="card-title mb-4">Details</h4>
                                </div>
                                <div class="col-6">
                                    @if ($data->type == 'pending')
                                        <div class="mt-3 d-flex gap-2">
                                            <a href="{{ route('admin.event.accept', $data->id) }}"
                                                class="font-size-14 btn btn-sm btn-outline-success waves-effect waves-light btnAction"
                                                data-action="approved">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="{{ route('admin.event.reject', $data->id) }}"
                                                class="font-size-14 btn btn-sm btn-outline-danger waves-effect waves-light btnAction"
                                                data-action="rejected">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div> --}}
                            <h4 class="card-title mb-4">Details</h4>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Event Category:</h5>
                                    <p class="text-muted">
                                        {{ $data->category->event_category ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">Event Name:</h5>
                                    <p class="text-muted">{{ $data->event_name }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Event Type:</h5>
                                    <p class="text-muted">{{ $data->event_type }}</p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">Location:</h5>
                                    <p class="text-muted">{{ $data->location }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="font-size-15">About Event:</h5>
                                    <p class="text-muted">{{ $data->about_event }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Start Date:</h5>
                                    <p class="text-muted">{{ $data->start_date }}</p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">End Date:</h5>
                                    <p class="text-muted">{{ $data->end_date }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Start Time:</h5>
                                    <p class="text-muted">{{ $data->start_time }}</p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">End Time:</h5>
                                    <p class="text-muted">{{ $data->end_time }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Status:</h5>
                                    <p class="text-muted">{!! get_badge_html($data->status) !!}</p>
                                </div>
                                @php
                                    $status = strtolower($data->type);
                                    $colorClass = match($status) {
                                        'approved' => 'bg-success',   // green
                                        'pending' => 'bg-warning',    // orange
                                        'rejected' => 'bg-danger',    // red
                                        default => 'bg-secondary',    // gray
                                    };
                                @endphp
                                <div class="col-6">
                                    <h5 class="font-size-15">Type:</h5>
                                    <span class="badge {{ $colorClass }} text-white px-3 py-1">
                                        {{ ucfirst($data->type) }}
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Banner Image:</h5>
                                    @if (!empty($data->banner_image))
                                        <a data-fancybox="gallery" href="{{ $data->banner_image }}" target="_blank">
                                            <img src="{{ $data->banner_image }}" alt="Business Image"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        </a>
                                    @else
                                        <p class="text-muted">No banner image</p>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <h4 class="card-title">Gallery:</h4>
                                    @if (!empty($data->eventImages) && count($data->eventImages) > 0)
                                        <div class="d-flex gap-2 flex-wrap">
                                            @foreach ($data->eventImages as $img)
                                                <a data-fancybox="gallery" href="{{ $img->image_path }}"
                                                    target="_blank">
                                                    <img src="{{ $img->image_path }}" alt="Business Image"
                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <h6 class="card-subtitle font-14 text-muted">-</h6>
                                    @endif
                                </div>
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
