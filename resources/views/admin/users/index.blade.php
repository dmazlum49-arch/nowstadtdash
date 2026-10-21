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
                            {{-- <div class="text-end">
                                <a href="{{route('admin.users.create')}}" class="btn btn-primary waves-effect btn-label waves-light"><i class="bx bx-plus label-icon"></i> Add</a>
                            </div> --}}
                            <h4 class="card-title">All Users</h4>
                            <table id="users-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Profile</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Type</th>
                                    <th>Status</th>
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
            $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.users.index') }}',
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'profile_image', orderable: false, searchable: false },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'mobile' },
                    { data: 'type' },
                    { data: 'status', orderable: false, searchable: false },
                    { data: 'action', orderable: false, searchable: false }
                ]
            });
        });
    </script>
@endsection
