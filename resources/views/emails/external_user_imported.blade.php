@component('mail::message', ['name' => $name, 'url' => $url, 'page_title' => $page_title])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        {{ __('Welcome to :app', ['app' => $page_title]) }}
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        {{ $name !== '' ? __('Hello :name.', ['name' => $name]) : __('Hello.') }}
        {{ __('You have just been imported into :app and an account has been created for you.', ['app' => $page_title]) }}
        {{ __('Simply sign in with the credentials of your organisation (email address or user name and the password you also use at your workplace) – a separate password for :app is not needed.', ['app' => $page_title]) }}
    </p>

    @component('mail::button', ['url' => $url])
        {{ __('Go to sign in') }}
    @endcomponent

    <p style="font-weight: 300; margin-top: 3em;">
        {{ __('Note: "Forgot password" does not apply to your account – your password is managed centrally by your organisation. If you have any questions, contact us at') }}
        <a href="mailto:{{ $sender_email }}">{{ $sender_email }}</a>.
    </p>

    <p style="font-weight: 300;">
        {{ __('Best regards,') }}<br>
        {{ $page_title }}
    </p>
@endcomponent
