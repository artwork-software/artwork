<?php

namespace Artwork\Modules\Vacation\Enums;

enum Vacation: string
{
    /** Urlaub (soll-wirksam) */
    case OFF_WORK = 'OFF_WORK';
    /** Nicht verfügbar (soll-neutral) */
    case NOT_AVAILABLE = 'NOT_AVAILABLE';
    /** Freier Tag laut Planung (ganz oder halb, siehe vacations.day_part) */
    case FREE_WORK = 'FREE_WORK';
    case AVAILABLE = 'AVAILABLE';

    /**
     * Kommentar ohne bloßen Statusnamen: Früher landete der Status selbst (z. B. „FREE_WORK“) als Kommentar
     * in Abwesenheiten und wurde beim Umstellen auf „Verfügbar“ in die Verfügbarkeit übernommen. Ein solcher
     * Kommentar ist kein Kommentar und wird zu null; alles andere bleibt unverändert.
     */
    public static function withoutBareStatus(?string $comment): ?string
    {
        if ($comment !== null && self::tryFrom(trim($comment)) !== null) {
            return null;
        }

        return $comment;
    }
}
