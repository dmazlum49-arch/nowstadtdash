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


                <table class="table table-bordered">
                    <tr>
                        <th>Reported By</th>
                        <td>{{ $data->user->name }}</td>
                    </tr>

                    <tr>
                        <th>Reported From</th>
                        <td>{{ $data->post->user->name ?? '-' }}</td>
                    </tr>


                    <tr>
                        <th>Report Reason</th>
                        <td>{{ $data->reason }}</td>
                    </tr>

                    <tr>
                        <th>Report Message</th>
                        <td>{{ $data->message }}</td>
                    </tr>
                    
                </table>

            </div>
        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
@endsection
