@extends('layouts.mail.app')
@section('content')
    <tr>
        <td style="padding: 50px 45px 25px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.dear') }} <strong>{{$user->name}},</strong></td>
    </tr>
    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550">{{ __('mail.you_recently_requested_to_reset_your_password') }} {{config('general_settings.SITE_NAME')}} {{ __('mail.account') }}.
        </td>
    </tr>
    <tr>
        <td style="padding: 0 45px;" width="550"><a href="{{route('admin.password.reset',$user->reset_token)}}"
                                                    style="font-size: 14px; font-family: arial; color: #ff5673; font-weight: bold; text-decoration: none; line-height: normal;">{{ __('mail.click_here_to_reset_your_password') }}</a></td>
    </tr>
@endsection
