@extends('layouts.mail.app')
@section('content')
    <tr>
        <td style="padding: 50px 45px 25px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.dear_user') }},</strong></td>
    </tr>
    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550">{{ __('mail.your_otp_for_update_email_is') }}: <strong>{{ $otp }}</strong>
        </td>
    </tr>
    <tr>
        <td style="padding: 0 45px;" width="550">{{ __('mail.this_otp_is_valid_for_the_next_10_minutes') }}</td>
    </tr>
    <tr>
        <td style="padding: 0 45px;" width="550">{{ __('mail.if_you_did_not_request_this_please_ignore_this_email') }}</td>
    </tr>
@endsection
