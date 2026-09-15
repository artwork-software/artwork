<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Link auf ein Objekt, das eine Aktion angelegt oder geaendert hat (core-undo; Konzept W3 §3).
 *
 * Es gibt im Repo keine Detailseite je Termin — der Kalender ist die Ansicht. Verlinkt wird
 * deshalb die bestehende Route `dashboard.redirect-to-calendar`, die den Kalender auf dem Termin
 * oeffnet (`routes/web.php:981`).
 */
class AiWriteLinkData extends Data
{
    public function __construct(
        public int $id,
        public string $url,
    ) {
    }
}
