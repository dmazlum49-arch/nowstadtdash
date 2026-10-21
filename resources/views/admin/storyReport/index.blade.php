@extends('layouts.admin.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Reported Posts</h4>

                            <table id="users-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Reported By</th>
                                        <th>Reported From</th>
                                        <th>Reason</th>
                                        <th>Date / Time</th>
                                        <th>Actions</th>
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

    {{-- Description Modal --}}
    <div class="modal fade" id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="fullDescriptionContent"></div>
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
                ajax: "{{ route('admin.story-report.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user_name'
                    },
                    {
                        data: 'reported_from'
                    },
                    {
                        data: 'reason'
                    },
                    {
                        data: 'date_added'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                drawCallback: function() {
                    $('.desc-preview').on('click', function() {
                        $('#fullDescriptionContent').html($(this).data('full'));
                        $('#descModal').modal('show');
                    });
                }
            });
        });

        // ===========================
        // SweetAlert Actions
        // ===========================

        // DELETE STORY
        $(document).on('click', '.delete-story', function() {
            let url = $(this).data('url');

            Swal.fire({
                title: "Delete Story?",
                text: "This story will be permanently deleted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e3342f",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get(url, function(res) {
                        Swal.fire("Deleted!", res.message, "success");
                        $('#users-table').DataTable().ajax.reload();
                    });
                }
            });
        });

        // IGNORE REPORT
        $(document).on('click', '.ignore-report', function() {
            let url = $(this).data('url');

            Swal.fire({
                title: "Ignore Report?",
                text: "The report will be marked as reviewed.",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#17a2b8",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Ignore",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get(url, function(res) {
                        Swal.fire("Ignored!", res.message, "success");
                        $('#users-table').DataTable().ajax.reload();
                    });
                }
            });
        });
    </script>
@endsection
