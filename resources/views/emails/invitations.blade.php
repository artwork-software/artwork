@component('mail::message', ['user' => $user])
{{ $user->first_name }} hat Sie eingeladen,  Artwork.tool zu nutzen.
@component('mail::button', ['url' => url("/users/invitations/accept?token=$token&email={$invitation->email}")])
Einladung akzeptieren
@endcomponent
@endcomponent
