<?php

namespace Artwork\Modules\Crm\Enums;

enum CrmSystemContactTypeEnum: string
{
    case FREELANCER = 'freelancer';
    case SERVICE_PROVIDER = 'service_provider';
    case MANUFACTURER = 'manufacturer';
    case ACCOMMODATION = 'accommodation';
    case ARTIST = 'artist';
    case USER = 'user';
    case TICKETING = 'ticketing';

    /**
     * Kontakte dieser Typen werden aus einer anderen Quelle gespiegelt und sind im CRM read-only —
     * Schreibzugriffe, Import und Zusammenführen würden beim nächsten Abgleich überschrieben.
     *
     * @return list<string>
     */
    public static function mirroredSlugs(): array
    {
        return [
            self::USER->value,
            self::FREELANCER->value,
            self::SERVICE_PROVIDER->value,
            self::TICKETING->value,
        ];
    }

    public static function isMirrored(?string $slug): bool
    {
        return in_array($slug, self::mirroredSlugs(), true);
    }
}
