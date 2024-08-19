@component('mail::message')
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        Einladung
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        Hallo,

        für dich wurde ein neuer {{ $page_title }}-Account eingerichtet.
        Um die Registrierung abzuschließen, klicke bitte auf den Button weiter unten. Aus Sicherheitsgründen ist diese
        Mail nur zeitlich begrenzt gültig.

        Bei Fragen kontaktiere uns gerne unter <a href="mailto:{{$email}}">{{ $email }}</a>
    </p>

    @component('mail::button', ['url' => url("/users/invitations/accept?token=$token&email={$invitation->email}")])
        Registrierung abschließen
    @endcomponent
@endcomponent
