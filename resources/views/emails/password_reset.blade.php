@component('mail::message', ['name' => $name, 'url' => $url])
    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        Artwork Passwort zurücksetzen
    </h1>
    <p style="font-weight: 300; margin-bottom: 3em;">
        Hallo {{ $name }}. Du erhälst diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für
        dein Konto erhalten haben. Der Link zum Zurücksetzen des Passworts wird in 60 Minuten ablaufen. Wenn du keine
        Passwortrücksetzung angefordert hast, sind keine weiteren Maßnahmen erforderlich. Mit besten Grüßen, artwork.
    </p>
    @component('mail::button', ['url' => $url])
        Passwort zurücksetzen
    @endcomponent
@endcomponent
