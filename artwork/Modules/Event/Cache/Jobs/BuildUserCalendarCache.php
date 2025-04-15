<?php

namespace Artwork\Modules\Event\Cache\Jobs;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildUserCalendarCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $from = null,
        public ?string $to = null
    ) {}

    public function handle(EventCalendarService $eventCalendarService): void
    {
        if (!$this->from) {
            $this->from = Carbon::now()->startOfYear();
        }

        if (!$this->to) {
            $this->to = Carbon::now()->endOfYear();
        }
        $filter = $this->user->shift_calendar_filter()->first();
        $settings = $this->user->calendar_settings;
        $rooms = Room::all();

        $eventCalendarService->filterRoomsEvents(
            $rooms->map(fn ($r) => clone $r),
            $filter,
            Carbon::parse($this->from),
            Carbon::parse($this->to),
            $settings
        );
    }
}
