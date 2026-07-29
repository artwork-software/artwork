@component('mail::message', ['name' => $name, 'url' => $url, 'page_title' => $page_title])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        Willkommen bei {{ $page_title }}
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        Hallo{{ $name !== '' ? ' ' . $name : '' }}. Du wurdest soeben in {{ $page_title }} importiert und ein Konto
        wurde für dich angelegt. Du meldest dich einfach mit deinen gewohnten Zugangsdaten deiner Organisation an
        (E-Mail-Adresse bzw. Benutzername und das Passwort, das du auch an deinem Arbeitsplatz verwendest) –
        ein separates Passwort für {{ $page_title }} ist nicht nötig.
    </p>

    @component('mail::button', ['url' => $url])
        Zur Anmeldung
    @endcomponent

    <p style="font-weight: 300; margin-top: 3em;">
        Hinweis: Die Funktion „Passwort vergessen" gilt für dein Konto nicht – dein Passwort wird zentral von
        deiner Organisation verwaltet. Bei Fragen erreichst du uns unter
        <a href="mailto:{{ $sender_email }}">{{ $sender_email }}</a>.
    </p>

    <p style="font-weight: 300;">
        Mit besten Grüßen,<br>
        {{ $page_title }}
    </p>
@endcomponent
