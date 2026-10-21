@extends('layouts.mail.app')
@section('content')
    <tr>
        <td style="padding: 50px 45px 25px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.dear') }} <strong>{{ $name }},</strong>
        </td>
    </tr>
    <tr>
        <td style="padding: 5px 45px 5px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.we_are_pleased_to_inform_you') }} {{ $event }} {{ __('mail.has_been_approved') }}.
        </td>
    </tr>
    <tr>
        <td style="padding: 5px 45px 5px 45px; font-family: arial; font-size: 15px; color: #333; line-height: 25px;"
            width="550">
            @if ($reason)
                <!-- <p><strong>Reason:</strong> {{ $reason }}</p> -->
            @endif
        </td>
    </tr>
@endsection
