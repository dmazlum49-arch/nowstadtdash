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

                        {{-- Tabs Header --}}
                        <ul class="nav nav-tabs d-flex w-100" style="font-size: 16px" id="infoTabs" role="tablist">
                            <li class="nav-item flex-fill text-center" role="presentation">
                                <button class="nav-link active w-100" id="business-tab" data-bs-toggle="tab"
                                    data-bs-target="#business-info" type="button" role="tab"
                                    aria-controls="business-info" aria-selected="true">
                                    Business Info
                                </button>
                            </li>

                            @if ($data->type === 'approved')
                                <li class="nav-item flex-fill text-center" role="presentation">
                                    <button class="nav-link w-100" id="service-tab" data-bs-toggle="tab"
                                        data-bs-target="#service-info" type="button" role="tab"
                                        aria-controls="service-info" aria-selected="false">
                                        Service Info
                                    </button>
                                </li>
                            @endif
                        </ul>

                        {{-- Tabs Content --}}
                        <div class="tab-content">
                            {{-- Business Info Tab --}}
                            <div class="tab-pane fade show active" id="business-info" role="tabpanel"
                                aria-labelledby="business-tab">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Details</h4>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="font-size-15">Business Category:</h5>
                                            <p class="text-muted">{{ $data->category->business_category ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-15">Business Name:</h5>
                                            <p class="text-muted">{{ $data->business_name }}</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="font-size-15">About Business:</h5>
                                            <p class="text-muted">{{ $data->about_business }}</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-15">City:</h5>
                                            <p class="text-muted">{{ $data->city }}</p>
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
                                                <a data-fancybox="gallery" href="{{ url($data->banner_image) }}"
                                                    target="_blank">
                                                    <img src="{{ url($data->banner_image) }}" alt="Business Image"
                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                </a>
                                            @else
                                                <p class="text-muted">No banner image</p>
                                            @endif
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-15">Logo:</h5>
                                            <a data-fancybox="gallery" href="{{ url($data->logo) }}" target="_blank">
                                                <img src="{{ url($data->logo) }}" alt="Business Image"
                                                    style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            </a>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <h4 class="card-title">Gallery:</h4>
                                            @if (!empty($data->businessImages) && count($data->businessImages) > 0)
                                                <div class="d-flex gap-2 flex-wrap">
                                                    @foreach ($data->businessImages as $img)
                                                        <a data-fancybox="gallery" href="{{ url($img->image_path) }}"
                                                            target="_blank">
                                                            <img src="{{ url($img->image_path) }}" alt="Business Image"
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
                                    <h4>Business Timings</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Status</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data->timings as $timing)
                                                <tr>
                                                    <td>{{ $timing->day }}</td>
                                                    <td>{{ $timing->is_open ? 'Open' : 'Closed' }}</td>
                                                    <td>{{ $timing->is_open ? $timing->start_time : '-' }}</td>
                                                    <td>{{ $timing->is_open ? $timing->end_time : '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Service Info Tab (Only if approved) --}}
                            @if ($data->type === 'approved')
                                <div class="tab-pane fade" id="service-info" role="tabpanel" aria-labelledby="service-tab">
                                    <div class="card-body">
                                        <h4 class="card-title">Service Info</h4>
                                        {{-- Add your dynamic service content here --}}
                                        @if ($data->services->count())
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Business Name</th>
                                                            <th>User Name</th>
                                                            <th>Title</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($data->services as $index => $service)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>
                                                                    {{ $service->business->business_name ?? 'N/A' }}
                                                                </td>
                                                                <td>{{ $service->user->name ?? 'N/A' }}</td>
                                                                <td>{{ $service->title }}</td>
                                                                <td>
                                                                    <a href="{{ route('admin.service.show', $service->id) }}"
                                                                        class="font-size-14 me-2 btn btn-sm btn-outline-primary waves-effect waves-light btnView"
                                                                        data-action="approved">
                                                                        <i class="mdi mdi-eye"></i>
                                                                    </a>
                                                                </td>

                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-danger">No Services found for this Business.</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('page_script')
@endsection
