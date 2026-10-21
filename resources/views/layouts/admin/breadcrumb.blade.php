@if(isset($breadcrumbs))
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{$title}}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{__('admin.dashboard')}}</a></li>
                        @foreach ($breadcrumbs as $key => $breadcrumb)
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb }}">{{ $key }}</a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endif
