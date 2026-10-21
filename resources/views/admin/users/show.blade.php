@extends('layouts.admin.app')

@section('head_css')
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <!-- Nav Tabs -->
                        <ul class="nav nav-tabs d-flex w-100" style="font-size: 16px" id="infoTabs" role="tablist">
                            <li class="nav-item flex-fill text-center" role="presentation">
                                <button class="nav-link active w-100" id="user-tab" data-bs-toggle="tab"
                                    data-bs-target="#user-info" type="button" role="tab" aria-controls="user-info"
                                    aria-selected="true">
                                    User Info
                                </button>
                            </li>
                            <li class="nav-item flex-fill text-center" role="presentation">
                                <button class="nav-link w-100" id="business-tab" data-bs-toggle="tab"
                                    data-bs-target="#business-info" type="button" role="tab"
                                    aria-controls="business-info" aria-selected="false">
                                    Business Info
                                </button>
                            </li>
                            <li class="nav-item flex-fill text-center" role="presentation">
                                <button class="nav-link w-100" id="event-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-info" type="button" role="tab" aria-controls="event-info"
                                    aria-selected="false">
                                    Event Info
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content p-3" id="infoTabsContent">
                            <!-- User Info Tab -->
                            <div class="tab-pane fade show active" id="user-info" role="tabpanel"
                                aria-labelledby="user-tab">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Personal Information</h4>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="font-size-15">Name:</h5>
                                            <p class="text-muted">
                                                {{ $data->name }}
                                            </p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-15">Email:</h5>
                                            <p class="text-muted">{{ $data->email }}</p>
                                        </div>

                                        <hr>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="font-size-15">Mobile:</h5>
                                            <p class="text-muted">{{ $data->country_code }} {{ $data->mobile }}</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-15">Location:</h5>
                                            <p class="text-muted">{{ $data->location }}</p>
                                        </div>
                                        <hr>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="font-size-15">Status:</h5>
                                            <p class="text-muted">{!! get_badge_html($data->status) !!}</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-15">Profile Image:</h5>
                                            @if (!empty($data->profile_image))
                                                <a data-fancybox="gallery" href="{{ url($data->profile_image) }}"
                                                    target="_blank">
                                                    <img src="{{ url($data->profile_image) }}" alt="Business Image"
                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                </a>
                                            @else
                                                <p class="text-muted">No Profile image</p>
                                            @endif
                                        </div>
                                    </div>
                                    <hr>
                                    @if ($data->type === 'businessuser')
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="font-size-15">Verified Status:</h5>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input verify-toggle"
                                                    type="checkbox"
                                                    id="verifySwitch{{ $data->id }}"
                                                    data-id="{{ $data->id }}"
                                                    {{ $data->is_verified ? 'checked' : '' }}>
                                                <label class="form-check-label" for="verifySwitch{{ $data->id }}">
                                                    {{ $data->is_verified ? 'Verified' : 'Not Verified' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    @endif
                                </div>
                            </div>

                            <!-- Business Info Tab -->
                            <div class="tab-pane fade" id="business-info" role="tabpanel" aria-labelledby="business-tab">
                                <h4 class="card-title mb-4">Business Information</h4>
                                @if ($data->businesses->count())
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Banner</th>
                                                    <th>Business Name</th>
                                                    <th>Category</th>
                                                    <th>Type</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($data->businesses as $index => $business)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            @if (!empty($business->banner_image))
                                                                <a data-fancybox="gallery"
                                                                    href="{{ url($business->banner_image) }}"
                                                                    target="_blank">
                                                                    <img src="{{ url($business->banner_image) }}"
                                                                        alt="Business Image"
                                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                                </a>
                                                            @else
                                                                <p class="text-muted">No banner image</p>
                                                            @endif
                                                        </td>
                                                        <td>{{ $business->business_name }}</td>
                                                        <td>{{ $business->category->business_category ?? '-' }}</td>
                                                        <td>{{ $business->type }}</td>
                                                        <td>
                                                            <a href="{{ route('admin.business.show', $business->id) }}"
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
                                    <p class="text-danger">No businesses found for this user.</p>
                                @endif
                            </div>

                            <!-- Event Info Tab -->
                            <div class="tab-pane fade" id="event-info" role="tabpanel" aria-labelledby="event-tab">
                                <h4 class="card-title mb-4">Event Information</h4>
                                @if ($data->events->count())
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Banner</th>
                                                    <th>Event Name</th>
                                                    <th>Event Category</th>
                                                    <th>Type</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($data->events as $index => $event)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            @if (!empty($event->banner_image))
                                                                <a data-fancybox="gallery"
                                                                    href="{{ url($event->banner_image) }}"
                                                                    target="_blank">
                                                                    <img src="{{ url($event->banner_image) }}"
                                                                        alt="Event Image"
                                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                                </a>
                                                            @else
                                                                <p class="text-muted">No banner image</p>
                                                            @endif
                                                        </td>
                                                        <td>{{ $event->event_name }}</td>
                                                        <td>{{ $event->category->event_category ?? '-' }}</td>
                                                        <td>{{ $event->type }}</td>
                                                        <td>
                                                            <a href="{{ route('admin.event.show', $event->id) }}"
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
                                    <p class="text-danger">No Event found for this user.</p>
                                @endif
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
$(document).on('change', '.verify-toggle', function () {
    let userId = $(this).data('id');
    let isChecked = $(this).is(':checked');
    let label = $(this).next('label');

    // Build the correct URL safely
    let url = "{{ route('admin.users.verify', ':id') }}".replace(':id', userId);

    // Disable toggle during request
    $(this).prop('disabled', true);

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (response) {
            if (response.success) {
                label.text(isChecked ? 'Verified' : 'Not Verified');
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        },
        error: function () {
            toastr.error('Something went wrong.');
        },
        complete: () => {
            // Re-enable the toggle
            $('.verify-toggle').prop('disabled', false);
        }
    });
});
</script>
@endsection
