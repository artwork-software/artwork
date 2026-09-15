<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Konflikt an einem Vorschau-Eintrag (core-write-endpoints-dry-run; Konzept W1 §2).
 *
 * `type`: `room_collision` (der Raum ist in der Zeit schon belegt) oder `missing_permission`
 * (die fragende Person darf dieses Objekt nicht anlegen/aendern/loeschen).
 */
class AiWriteConflictData extends Data
{
    public const ROOM_COLLISION = 'room_collision';

    public const MISSING_PERMISSION = 'missing_permission';

    public function __construct(
        public string $type,
        public string $message,
        public ?AiRefData $event,
    ) {
    }
}
