<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>{{isset($title) ? ucfirst($title).' | ':''}}{{ ucfirst(config('general_settings.SITE_NAME')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{checkFileExist(config('general_settings.FAVICON'))}}">
    <!-- Bootstrap Css -->
    <link href="{{url('assets/admin/css/bootstrap.min.css')}}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{url('assets/admin/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- Toastr Css -->
    <link href="{{url('assets/admin/libs/toastr/build/toastr.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{url('assets/admin/css/app.css')}}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{url('assets/admin/css/app-change.css')}}" id="app-style" rel="stylesheet" type="text/css" />
    @yield('head_css')
    <style>
        html {
            background-image: url({{ url('assets/admin/images/123.png') }});
            background-size: cover;
        }

        body {
            background: transparent;
        }
    </style>
</head>
<body>
    @yield('content')
    {!! get_error_html($errors) !!}
    <!-- JAVASCRIPT -->
    <script src="{{url('assets/admin/libs/jquery/jquery.min.js')}}"></script>
    <script src="{{url('assets/admin/libs/jquery-validation/jquery.validate.min.js')}}"></script>
    <script src="{{url('assets/admin/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{url('assets/admin/libs/metismenu/metisMenu.min.js')}}"></script>
    <script src="{{url('assets/admin/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{url('assets/admin/libs/node-waves/waves.min.js')}}"></script>
    <script src="{{url('assets/admin/libs/toastr/build/toastr.min.js')}}"></script>
    <!-- App js -->
    <script src="{{url('assets/admin/js/app.js')}}"></script>
    <script>
        @if(session()->has('message'))
            toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };

            @if(session('message')['type'] === 'success')
            toastr.success("{{ session('message')['text'] }}");
            @elseif(session('message')['type'] === 'error')
            toastr.error("{{ session('message')['text'] }}");
            @elseif(session('message')['type'] === 'warning')
            toastr.warning("{{ session('message')['text'] }}");
            @elseif(session('message')['type'] === 'info')
            toastr.info("{{ session('message')['text'] }}");
            @endif
        @endif
    </script>
    @yield('page_script')
</body>
</html>
