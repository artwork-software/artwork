<?php

namespace Artwork\Modules\User\Enums;

enum MemberSortEnum: string
{
    case ALPHABETICALLY_ASCENDING = 'ALPHABETICALLY_ASCENDING';

    case ALPHABETICALLY_DESCENDING = 'ALPHABETICALLY_DESCENDING';

    case CHRONOLOGICALLY_ASCENDING = 'CHRONOLOGICALLY_ASCENDING';

    case CHRONOLOGICALLY_DESCENDING = 'CHRONOLOGICALLY_DESCENDING';

    /**
     * @return array<int, string>|string
     */
    public function mapToColumn(int $userType): array|string
    {
        return match ($this) {
            //userType = 1 -> freelancers table, otherwise service_providers table
            self::ALPHABETICALLY_ASCENDING,
            self::ALPHABETICALLY_DESCENDING => $userType === 1 ? ['last_name', 'first_name'] : 'provider_name',
            self::CHRONOLOGICALLY_ASCENDING,
            self::CHRONOLOGICALLY_DESCENDING => 'created_at'
        };
    }

    public function mapToDirection(): string
    {
        return match ($this) {
            self::ALPHABETICALLY_ASCENDING,
            self::CHRONOLOGICALLY_ASCENDING  => 'asc',
            self::ALPHABETICALLY_DESCENDING,
            self::CHRONOLOGICALLY_DESCENDING => 'desc'
        };
    }
}
