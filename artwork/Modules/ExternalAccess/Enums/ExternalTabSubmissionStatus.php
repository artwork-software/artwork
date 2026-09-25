<?php

namespace Artwork\Modules\ExternalAccess\Enums;

/**
 * Stand eines freigegebenen Tabs aus Sicht einer externen Person (pro Scope).
 *  - OPEN: in Bearbeitung, noch nicht abgesendet
 *  - SUBMITTED: abgesendet, für die externe Person gesperrt bis zur internen Prüfung
 *  - CONFIRMED: intern bestätigt, bleibt gesperrt
 *  - RETURNED: intern zur Überarbeitung zurückgegeben, wieder bearbeitbar
 */
enum ExternalTabSubmissionStatus: string
{
    case OPEN = 'open';
    case SUBMITTED = 'submitted';
    case CONFIRMED = 'confirmed';
    case RETURNED = 'returned';

    public function locksExternalEditing(): bool
    {
        return $this === self::SUBMITTED || $this === self::CONFIRMED;
    }
}
