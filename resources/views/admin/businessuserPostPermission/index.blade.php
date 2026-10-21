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
                                <a href="{{route('admin.business-post-permissions.create')}}" class="btn btn-primary waves-effect btn-label waves-light"><i class="bx bx-plus label-icon"></i> Add</a>
                            </div>
                            <h4 class="card-title">All Business Permissions</h4>
                            <table id="users-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Name</th>
                                    <th>Max Posts</th>
                                    <th>Max Events</th>
                                    <th>Max Push Notifications</th>
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
                    url: '{{ route('admin.business-post-permissions.index') }}',
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'user_name' },
                    { data: 'max_posts' },
                    { data: 'max_events' },
                    { data: 'max_push_notifications' },
                    { data: 'action', orderable: false, searchable: false }
                ]
            });
        });
    </script>
@endsection
