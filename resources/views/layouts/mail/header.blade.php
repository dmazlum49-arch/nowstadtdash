<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html, body {
            width: max-content;
            margin: 0 auto;
        }
 
        body {
            margin: 0;
            padding: 0;
            background: #ccc;
            font-size: 15px;
            line-height: normal;
            color: #000;
            font-family: arial, monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-danger {
            background-color: #dc3545;
        }
    </style>
</head>
<body >
<table align="center" cellpadding="0" cellspacing="0" width="100%">
    <tbody>
    <tr>
        <td align="center">
            <table align="center" cellpadding="0" cellspacing="0" style="background: #fff;" width="640">
                <tbody>
                <tr>
                    <td align="center" style="padding:15px 45px; background-color:#2F41FF;" width="540"><a
                            href="javascript:;" style="display: inline-block;" target="_blank"><img
                                  alt="{{config('general_settings.SITE_NAME')}} logo"
                                  src="{{ url(config('general_settings.SITE_LOGO')) }}"
                                  {{-- src="{{checkFileExist(config('general_settings.SITE_LOGO'))}}" --}}
                                style="width: 150px;"/>
                        </a></td>
                </tr>