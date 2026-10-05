@component('mail::message', ['url' => $externalLoginUrl, 'page_title' => $pageTitle])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">{{ $subject }}</h1>

    <p style="font-weight: 300; margin-bottom: 1em;">
        @if($status === 'confirmed')
            {{ __(':reviewer has confirmed your data for ":tab" in project ":project". Thank you!', ['reviewer' => $reviewer, 'tab' => $tabName, 'project' => $projectName]) }}
        @else
            {{ __(':reviewer has returned your data for ":tab" in project ":project" for revision. You can edit it again and submit it once more.', ['reviewer' => $reviewer, 'tab' => $tabName, 'project' => $projectName]) }}
        @endif
    </p>

    @if($comment)
        <p style="font-weight: 300; margin-bottom: 1em;">
            {{ __('Comment: :comment', ['comment' => $comment]) }}
        </p>
    @endif

    @component('mail::button', ['url' => $externalLoginUrl])
        {{ __('Log in to view your data') }}
    @endcomponent
@endcomponent
