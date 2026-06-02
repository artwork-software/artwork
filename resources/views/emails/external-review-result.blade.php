@component('mail::message', ['url' => $externalLoginUrl, 'page_title' => $pageTitle])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">{{ $subject }}</h1>

    @if($status === 'rejected')
        <p style="font-weight: 300; margin-bottom: 1em;">
            {{ __('Your last changes were partially or fully declined.') }}
        </p>
    @else
        <p style="font-weight: 300; margin-bottom: 1em;">
            {{ __('Approved fields: :count', ['count' => $approvedCount]) }}<br>
            {{ __('Rejected fields: :count', ['count' => $rejectedCount]) }}
        </p>
    @endif

    @if($rejectionReason)
        <p style="font-weight: 300; margin-bottom: 1em;">
            {{ __('Reason: :reason', ['reason' => $rejectionReason]) }}
        </p>
    @endif

    @component('mail::button', ['url' => $externalLoginUrl])
        {{ __('Log back in to view your data') }}
    @endcomponent
@endcomponent
