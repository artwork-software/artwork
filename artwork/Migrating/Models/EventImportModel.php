<?php

namespace Artwork\Migrating\Models;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;

class EventImportModel
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
        public readonly string $description,
        public readonly string $room,
        public readonly string $date,
        public readonly ?string $start,
        public readonly ?string $end,
        public readonly string $eventType,
    ) {
    }
}
