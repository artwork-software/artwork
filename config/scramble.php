<?php

/*
|--------------------------------------------------------------------------
| OpenAPI-Erzeugung (dedoc/scramble)
|--------------------------------------------------------------------------
|
| Nur die vom Standard abweichenden Schlüssel; alles Übrige kommt aus der
| Paket-Konfiguration.
|
| Scramble ist eine Entwicklungsabhängigkeit und erzeugt die Spezifikation in der
| CI. Die Laufzeit-Oberfläche unter /docs/api wird zusätzlich in
| RouteServiceProvider::boot() abgeschaltet — der Container installiert auch
| Dev-Abhängigkeiten, die Route wäre also sonst vorhanden.
|
*/

return [

    // Dokumentiert wird ausschließlich die versionierte Maschinen-API. Die übrigen
    // /api-Pfade sind SPA-interne Endpunkte und gehören nicht in eine Partner-Doku.
    'api_path' => 'api/v1',

    'export_path' => 'openapi.json',

    'info' => [
        'version' => '1.0.0',
        'description' => 'Machine API of artwork. Authenticate with a bearer token created under '
            . 'Tool settings → Interfaces. Every endpoint requires the scope noted on it.',
    ],

    'ui' => [
        'title' => 'artwork Machine API',
    ],

];
