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
                                <a href="{{ route('user.service.create') }}" class="btn btn-primary waves-effect btn-label waves-light"><i class="bx bx-plus label-icon"></i> Add</a>
                            </div>
                            <h4 class="card-title">Services</h4>
                            <table id="contents-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Business Name</th>
                                        <th>Title</th>
                                        <th>description</th>
                                        <th>Price</th>
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

    <div class="modal fade" id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="descModalLabel">Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="fullDescriptionContent">
                    <!-- Full description will appear here -->
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
                    url: '{{ route('user.service.index') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'business_name'
                    },
                    {
                        data: 'title'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'price'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                drawCallback: function() {
                    // Bind click after each table draw
                    $('.desc-preview').on('click', function() {
                        const fullText = $(this).data('full');
                        $('#fullDescriptionContent').html(fullText);
                        $('#descModal').modal('show');
                    });
                }
            });
        });
    </script>

@endsection
