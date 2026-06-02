@component('mail::message', ['url' => $url, 'page_title' => $company])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        {{ __('You have been invited to :company', ['company' => $company]) }}
    </h1>
    <p style="font-weight: 300; margin-bottom: 1em;">
        {{ __(':invitedBy has invited you to enter your data in :company.', ['invitedBy' => $invitedByName, 'company' => $company]) }}
    </p>
    @if($projectName)
        <p style="font-weight: 300; margin-bottom: 1em;">
            {{ __('You will also gain access to selected information for the project ":project".', ['project' => $projectName]) }}
        </p>
    @endif
    @component('mail::button', ['url' => $url])
        {{ __('Open invitation') }}
    @endcomponent
    <p style="font-weight: 300; margin-top: 2em;">
        {{ __('This invitation link is valid for :minutes minutes.', ['minutes' => $lifetimeMinutes]) }}
    </p>
    <p style="font-weight: 300;">
        {{ __('If you did not expect this invitation, please ignore this email.') }}
    </p>
@endcomponent
