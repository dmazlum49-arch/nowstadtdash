@extends('layouts.admin.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- Title & Breadcrumb -->
            <div class="row">
                <div class="col-12">
                     <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">{{ $title }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Moderation</li>
                                <li class="breadcrumb-item active">{{ $title }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                 <div class="col-12">
                     <a href="{{ route('admin.moderation.posts') }}" class="btn btn-primary me-2">Flagged Posts</a>
                     <a href="{{ route('admin.moderation.comments') }}" class="btn btn-outline-primary me-2">Flagged Comments</a>
                     <a href="{{ route('admin.moderation.messages') }}" class="btn btn-outline-primary me-2">Flagged Messages</a>
                 </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Flagged Posts</h4>

                            <table id="moderation-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>User</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Flagged At</th>
                                        {{-- <th>Details</th> --}}
                                        {{-- <th>Actions</th> --}}
                                    </tr>
                                </thead>
                                <tbody></tbody>
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
            $('#moderation-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.moderation.posts') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'user_name' },
                    { data: 'title' },
                    { data: 'description' },
                    { data: 'flagged_at' },
                    // { data: 'details' },
                    // { data: 'action', orderable: false, searchable: false },
                ]
            });
        });
    </script>
@endsection
