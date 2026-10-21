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
                            @if(\App\Models\Business::where('user_id', auth()->user()->primaryId())->count() == 0)
                            <div class="text-end">
                                <a href="{{ route('user.business.create') }}" class="btn btn-primary waves-effect btn-label waves-light"><i class="bx bx-plus label-icon"></i> Add</a>
                            </div>
                            @endif
                            <h4 class="card-title">Business</h4>
                            <table id="contents-table" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Logo</th>
                                        <th>User Name</th>
                                        <th>Business Name</th>
                                        <th>Business Category</th>
                                        <th>City</th>
                                        <th>Today's Code</th>
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
                    url: '{{ route('user.business.index') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'logo'
                    },
                    {
                        data: 'user_name'
                    },
                    {
                        data: 'business_name'
                    },
                    {
                        data: 'business_category_id'
                    },
                    {
                        data: 'city'
                    },
                    {
                        data: 'daily_code'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $(document).on('click', '.btnVerifyCode', function() {
                var businessId = $(this).data('id');
                var url = $(this).data('url');

                Swal.fire({
                    title: 'Verify User Code',
                    text: 'Enter the 6-digit code provided by the user',
                    input: 'text',
                    inputAttributes: {
                        maxlength: 6,
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Verify',
                    showLoaderOnConfirm: true,
                    preConfirm: (code) => {
                        if (!code || code.length !== 6) {
                            Swal.showValidationMessage('Please enter a valid 6-digit code');
                            return false;
                        }
                        return $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                business_id: businessId,
                                code: code
                            }
                        }).then(response => {
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error.responseJSON.message}`
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Success!',
                            text: result.value.message,
                            icon: 'success'
                        });
                    }
                });
            });

            $(document).on('click', '.btnRegenerateCode', function() {
                var businessId = $(this).data('id');
                var url = $(this).data('url');

                Swal.fire({
                    title: 'Regenerate Daily Code?',
                    text: "This will change the today's 4-digit code for this business.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, regenerate it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                business_id: businessId
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire(
                                        'Regenerated!',
                                        response.message + ' New code: ' + response.daily_code,
                                        'success'
                                    );
                                    $('#contents-table').DataTable().ajax.reload();
                                }
                            },
                            error: function(error) {
                                Swal.fire(
                                    'Error!',
                                    error.responseJSON.message || 'Something went wrong.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>

    
@endsection
