@extends('layouts.mail.app')
@section('content')
    <tr>
        <td style="padding: 50px 45px 25px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.hello') }} {{ $business->user->name }},</strong></td>
    </tr>
    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550"> {{ __('mail.we_are_happy_to_inform_you') }}
        </td>
    </tr>
    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550"> {{ __('mail.your_profile_is_now_live_in_the_app') }}
        </td>
    </tr>
    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550"> {{ __('mail.attached_youll_find_your_personal_QR_code') }}
        </td>
    </tr>
    <tr>
        {{-- <td style="padding: 0 45px;" width="550"><img src="{{ $qrCodeUrl  }}" alt="QR Code" style="width:200px;height:200px;" /></td> --}}
    </tr>

    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550">🔐 {{ __('mail.important_note') }}
        </td>
    </tr>
    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550"> {{ __('mail.to_prevent_misuse') }}
        </td>
    </tr>

    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550">{{ __('mail.if_you_have_any_questions_or_need_support') }}
        </td>
    </tr>

    <tr>
        <td style="padding: 0 45px 5px; font-family: arial; font-size: 14px; color: #333; line-height: normal;"
            width="550">{{ __('mail.we_are_excited_to_have_you_on_board') }}
        </td>
    </tr>
@endsection
