@component('mail::message', ['url' => $url, 'page_title' => $page_title])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        {{ __('Your login link for :app', ['app' => $page_title]) }}
    </h1>
    <p style="font-weight: 300; margin-bottom: 3em;">
        {{ __('You are receiving this email because a login was requested for your external access. The link will expire in :minutes minutes. If you did not request this, no further action is required.', ['minutes' => $lifetime_minutes]) }}
    </p>
    @component('mail::button', ['url' => $url])
        {{ __('Open login link') }}
    @endcomponent
@endcomponent
