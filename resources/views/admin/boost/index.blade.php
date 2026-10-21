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
                            <div class="text-end">
                                <a href="{{ route('admin.boost.create') }}"
                                    class="btn btn-primary waves-effect btn-label waves-light"><i
                                        class="bx bx-plus label-icon"></i> Add</a>
                            </div>
                            <h4 class="card-title">Boosts</h4>
                            <table id="contents-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Business Name</th>
                                        <th>Multiplier / Badge</th>
                                        <th>Claims</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>Start Time</th>
                                        <th>End Date</th>
                                        <th>End Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    <script>
        $(function() {
            $('#contents-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.boost.index') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'business'
                    },
                    {
                        data: 'multiplier'
                    },
                    {
                        data: 'claims_count',
                        searchable: false
                    },
                    {
                        data: 'status',
                        searchable: false
                    },
                    {
                        data: 'start_date'
                    },
                    {
                        data: 'start_time'
                    },
                    {
                        data: 'end_date'
                    },
                    {
                        data: 'end_time'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
    </script>
@endsection
