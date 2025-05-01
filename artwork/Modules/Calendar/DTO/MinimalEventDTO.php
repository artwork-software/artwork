<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\SeriesEvents\Models\SeriesEvents;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;

class MinimalEventDTO extends Data
{
    public bool $isMinimal = true; // this is used by frontend, dont remove it
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public int $roomId,
        public array $daysOfEvent,
    ) {
    }

}
