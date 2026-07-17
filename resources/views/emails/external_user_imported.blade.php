@component('mail::message', ['name' => $name, 'url' => $url, 'page_title' => $page_title])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        Willkommen bei {{ $page_title }}
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        Hallo{{ $name !== '' ? ' ' . $name : '' }}. Du wurdest soeben in {{ $page_title }} importiert und ein Konto
        wurde für dich angelegt. Bevor du dich anmelden kannst, musst du einmalig dein Passwort festlegen. Klicke dazu
        auf den Button weiter unten. Aus Sicherheitsgründen ist dieser Link nur zeitlich begrenzt gültig.
    </p>

    @component('mail::button', ['url' => $url])
        Passwort festlegen
    @endcomponent

    <p style="font-weight: 300; margin-top: 3em;">
        Solltest du den Link verpasst haben, kannst du dein Passwort jederzeit über die Funktion
        „Passwort vergessen" auf der Anmeldeseite neu anfordern. Bei Fragen erreichst du uns unter
        <a href="mailto:{{ $sender_email }}">{{ $sender_email }}</a>.
    </p>

    <p style="font-weight: 300;">
        Mit besten Grüßen,<br>
        {{ $page_title }}
    </p>
@endcomponent
