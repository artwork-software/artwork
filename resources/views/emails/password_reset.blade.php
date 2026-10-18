@component('mail::message', ['name' => $name, 'url' => $url, 'page_title' => $page_title])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        {{ __('Reset password for :app', ['app' => $page_title]) }}
    </h1>
    <p style="font-weight: 300; margin-bottom: 3em;">
        {{ __('Hello :name. You are receiving this email because we received a password reset request for your account.', ['name' => $name]) }}
        {{ __('The password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.') }}
        {{ __('Best regards, :app.', ['app' => 'artwork']) }}
    </p>
    @component('mail::button', ['url' => $url])
        {{ __('Reset password') }}
    @endcomponent
@endcomponent
