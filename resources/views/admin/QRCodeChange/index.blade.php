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
                            <h4 class="card-title">Change Qr Code</h4>
                            <table id="contents-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>User Name</th>
                                        <th>Business Name</th>
                                        <th>Description</th>
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
                    url: '{{ route('admin.business.qr.change.index') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user_name'
                    },
                    {
                        data: 'business_name'
                    },
                    {
                        data: 'description'
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $(document).on('click', '.btnAction', function(e) {
                e.preventDefault();

                const actionUrl = $(this).attr('href');
                const actionType = $(this).data('action');

                const isReject = actionType === 'rejected';

                Swal.fire({
                    title: isReject ? "Reject Request?" : "Approve Request?",
                    html: isReject ?
                        `<p>You are about to reject this request.</p>
                   <textarea id="reject_reason" class="form-control" placeholder="Enter rejection reason" rows="2"></textarea>` :
                        "Are you sure you want to Approve this request?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#34c38f",
                    cancelButtonColor: "#f46a6a",
                    confirmButtonText: isReject ? "Yes, Reject" : "Yes, Approve",
                    cancelButtonText: "Cancel",
                    preConfirm: () => {
                        if (isReject) {
                            const reason = $('#reject_reason').val().trim();
                            if (!reason) {
                                Swal.showValidationMessage("Rejection reason is required");
                                return false;
                            }
                            return {
                                reason
                            };
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const postData = {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                        };

                        if (isReject) {
                            postData.reason = result.value.reason;
                        }

                        $.ajax({
                            url: actionUrl,
                            type: 'POST',
                            data: postData,
                            success: function(response) {
                                Swal.fire({
                                    title: "Success!",
                                    text: response.message ||
                                        "Action completed.",
                                    icon: "success",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                                $('.table').DataTable().ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: "Error!",
                                    text: xhr.responseJSON?.message ||
                                        "Something went wrong.",
                                    icon: "error",
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
