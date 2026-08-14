<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verfügbare Ereignisse
    |--------------------------------------------------------------------------
    |
    | Schlüssel ist der Ereignisname, Wert die Beschreibung für die Oberfläche.
    | Nur hier eingetragene Namen lassen sich abonnieren — ein Tippfehler im
    | Abonnement fiele sonst erst auf, wenn die Zustellung ausbleibt.
    |
    | Konvention für Namen: <domäne>.<gegenstand>.<vorgang>, z. B. event.ticketing.released.
    | Module tragen ihre Ereignisse hier ein; das Webhook-Modul selbst kennt keine Domäne.
    |
    */

    'events' => [
        'webhook.test' => 'Test event — triggered manually via artisan webhooks:ping',
    ],

];
