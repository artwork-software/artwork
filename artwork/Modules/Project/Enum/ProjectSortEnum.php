<?php

namespace Artwork\Modules\Project\Enum;

enum ProjectSortEnum: string
{
    case ALPHABETICALLY_ASCENDING = 'ALPHABETICALLY_ASCENDING';

    case ALPHABETICALLY_DESCENDING = 'ALPHABETICALLY_DESCENDING';

    case CHRONOLOGICALLY_ASCENDING = 'CHRONOLOGICALLY_ASCENDING';

    case CHRONOLOGICALLY_DESCENDING = 'CHRONOLOGICALLY_DESCENDING';

    /**
     * @return array<int, string>|string
     */
    public function mapToColumn(): array|string
    {
        return match ($this) {
            //projects table
            self::ALPHABETICALLY_ASCENDING,
            self::ALPHABETICALLY_DESCENDING => 'name',
            //events table
            self::CHRONOLOGICALLY_ASCENDING,
            self::CHRONOLOGICALLY_DESCENDING => [
                'start_time',
                'end_time'
            ]
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
