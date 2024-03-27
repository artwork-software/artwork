@component('mail::message')

    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        Artwork-Einladung
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        Hallo,
        Willkommen bei artwork. Schön, dass du da bist! Für dich wurde im artwork-System ein neuer Account mit deiner
        E-Mail-Adresse {{ $invitation->email }} angelegt. Um die Registrierung abzuschließen, klicke bitte auf den
        Button um dir einen Account zu erstellen. Aus Sicherheitsgründen ist diese Mail nur zeitlich begrenzt gültig.
        <br>
        <br>
        Bei Fragen kontaktiere uns gerne.
        @if($super_user_email)
            <a href="mailto:{{ $super_user_email }}">{{ $super_user_email }}</a>.
        @endif
    </p>

    @component('mail::button', ['url' => url("/users/invitations/accept?token=$token&email={$invitation->email}")])
        Einladung akzeptieren
    @endcomponent
@endcomponent
