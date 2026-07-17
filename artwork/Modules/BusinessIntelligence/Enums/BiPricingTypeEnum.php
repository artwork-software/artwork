<?php

namespace Artwork\Modules\BusinessIntelligence\Enums;

/**
 * Rechenrolle einer Besucher*innen-Kategorie: bestimmt, wie die Kategorie in
 * Quoten (Freikarten-, Ermäßigungs-, Zahlendenquote) und in der Summe der
 * verkauften Karten zählt — unabhängig vom frei wählbaren Kategorienamen.
 */
enum BiPricingTypeEnum: string
{
    case FULL = 'full';
    case REDUCED = 'reduced';
    case FREE = 'free';

    public function isPaid(): bool
    {
        return $this !== self::FREE;
    }
}
