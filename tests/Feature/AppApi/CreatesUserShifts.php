<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

trait CreatesUserShifts
{
    private function createShiftForUser(User $user, Carbon $date, array $shiftAttributes = []): Shift
    {
        $event = Event::factory()->create([
            'start_time' => $date->copy()->setTime(18, 0),
            'end_time' => $date->copy()->setTime(23, 0),
        ]);

        $shift = Shift::factory()->create($shiftAttributes + [
            'event_id' => $event->id,
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'start' => '18:00',
            'end' => '23:00',
            'break_minutes' => 30,
            'is_committed' => true,
        ]);

        $this->assignToShift($shift, $user);

        return $shift;
    }

    private function assignToShift(Shift $shift, User $user): void
    {
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);
    }
}
