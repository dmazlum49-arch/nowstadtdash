@extends('layouts.mail.app')
@section('content')
    <tr>
        <td style="padding: 50px 45px 25px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.hello_admin') }},</strong>
        </td>
    </tr>
    <tr>
        <td style="padding: 5px 45px 5px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.a_new_business_as_been') }}
        </td>
    </tr>
    <tr>
        <td style="padding: 20px 45px; font-family: arial; font-size: 15px; color: #333;">
            <p><strong> {{ __('mail.business_details') }} :</strong></p>

            <p><strong>{{ __('mail.business_name') }}:</strong> {{ $business->business_name }}</p>
            <p><strong>{{ __('mail.category') }}:</strong> {{ $business->category->business_category ?? 'N/A' }}</p>
            <p><strong>{{ __('mail.location') }}:</strong> {{ $business->city ?? 'N/A' }}</p>
            <p><strong>{{ __('mail.website') }}:</strong>
                @if(!empty($business->website))
                    <a href="{{ $business->website }}" target="_blank">{{ $business->website }}</a>
                @else
                    N/A
                @endif
            </p>
            <p><strong>{{ __('mail.about') }}:</strong> {{ $business->about_business ?? 'N/A' }}</p>
            <p><strong>{{ __('mail.submitted_by') }}:</strong> {{ $name }}</p>
            <p><strong>{{ __('mail.email') }}:</strong> {{ $business->user->email ?? 'N/A' }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding: 5px 45px 5px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.please_log_in_to_the_admin') }}
        </td>
    </tr>
@endsection
