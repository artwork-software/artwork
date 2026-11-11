<?php

namespace Artwork\Modules\AiAssistant\Support;

/**
 * Was der `BatchRecorder` zurueckmeldet (core-undo; Konzept W3 §3).
 *
 * Die Antwort auf eine ausgefuehrte Aktion nennt beides: die Batch-ID fuer den Revert und den
 * Zeitpunkt, bis zu dem er moeglich ist — die Bar rendert daraus „Rueckgaengig" mit Restzeit.
 */
final class RecordedBatch
{
    public function __construct(
        public readonly string $id,
        public readonly string $undoUntil,
    ) {
    }
}
