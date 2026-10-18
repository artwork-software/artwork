@component('mail::message')
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        {{ __('Invitation') }}
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        {{ __('Hello,') }}

        {{ __('a new :app account has been set up for you.', ['app' => $page_title]) }}
        {{ __('To complete your registration, please click the button below. For security reasons this email is only valid for a limited time.') }}

        {{ __('If you have any questions, please contact us at') }} <a href="mailto:{{$email}}">{{ $email }}</a>
    </p>

    @component('mail::button', ['url' => url("/users/invitations/accept?token=$token&email=" . urlencode($invitation->email))])
        {{ __('Complete registration') }}
    @endcomponent
@endcomponent
