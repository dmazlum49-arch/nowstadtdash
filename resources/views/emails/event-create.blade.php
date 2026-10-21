@extends('layouts.mail.app')
@section('content')
    <tr>
        <td style="padding: 50px 45px 25px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.hello_admin') }},</strong>
        </td>
    </tr>
    <tr>
        <td style="padding: 5px 45px 5px 45px; font-family: arial; font-size: 15px; color: #333; line-height: normal;"
            width="550">{{ __('mail.a_new_event_has_been_submitted_for_verification') }}</td>
    </tr>
    <tr>
        <td style="padding: 20px 45px; font-family: arial; font-size: 15px; color: #333;">
            <p><strong>{{ __('mail.event_details') }}:</strong></p>

            <p><strong>{{ __('mail.event_name') }}:</strong> {{ $event->event_name }}</p>
            <p><strong>{{ __('mail.category') }}:</strong> {{ $event->category->event_category ?? 'N/A' }}</p>
            <p><strong>{{ __('mail.description') }}:</strong> {{ $event->about_event }}</p>
            <p><strong>{{ __('mail.location') }}:</strong> {{ $event->location }}</p>
            <p><strong>{{ __('mail.start') }}:</strong> {{ $event->start_date }} at {{ $event->start_time }}</p>
            <p><strong>{{ __('mail.end') }}:</strong> {{ $event->end_date }} at {{ $event->end_time }}</p>

            <p><strong>{{ __('mail.submitted_by') }}:</strong></p>
            <p>{{ $user->name }}</p>
            <p>{{ $user->email }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding: 20px 45px; font-family: arial; font-size: 15px; color: #333;">
            {{ __('mail.please_log_in_to_the_admin') }}
        </td>
    </tr>
@endsection
