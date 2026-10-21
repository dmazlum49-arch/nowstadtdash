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
                                    <h5 class="font-size-15">Business Name:</h5>
                                    <p class="text-muted">{{ $data->business->business_name }}</p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">Label:</h5>
                                    <p class="text-muted">{{ $data->label ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">Start Date:</h5>
                                    <p class="text-muted">{{ $data->start_date }}</p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">Start Time:</h5>
                                    <p class="text-muted">{{ $data->start_time }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="font-size-15">End Date:</h5>
                                    <p class="text-muted">{{ $data->end_date }}</p>
                                </div>
                                <div class="col-6">
                                    <h5 class="font-size-15">End Time:</h5>
                                    <p class="text-muted">{{ $data->end_time }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-4">
                                    <h5 class="font-size-15">Boost Multiplier:</h5>
                                    <p><span class="badge bg-primary fs-6">{{ $data->multiplier ?? 2 }}x Nowis</span></p>
                                </div>
                                <div class="col-4">
                                    <h5 class="font-size-15">Points Awarded:</h5>
                                    <p class="text-muted">{{ $data->points ?? (( $data->multiplier ?? 2 ) * 10) }} Nowis</p>
                                </div>
                                <div class="col-4">
                                    <h5 class="font-size-15">Total Users Claimed:</h5>
                                    <p><span class="badge bg-success fs-6">{{ $data->claims->count() }}</span></p>
                                </div>
                            </div>

                            @if($data->claims->isNotEmpty())
                                <hr>
                                <h5 class="font-size-15 mb-3">Claimed Users:</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>User Name</th>
                                                <th>Points Awarded</th>
                                                <th>Claimed Date & Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data->claims as $index => $claim)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $claim->user->name ?? 'N/A' }}</td>
                                                    <td><span class="badge bg-success">+{{ $claim->points_awarded }} Nowis</span></td>
                                                    <td>{{ $claim->created_at->timezone('Europe/Vienna')->format('Y-m-d H:i:s') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
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
