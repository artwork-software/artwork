<?php

namespace Tests\Feature\Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

class EventServiceTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testGetDaysWithEventsAndTotalPlannedWorkingHoursForUserDoesNotQueryUserIdColumn(): void
    {
        $user = User::factory()->create();
        $shiftQualification = ShiftQualification::query()->create([
            'icon' => 'briefcase-icon',
            'name' => 'Worker',
            'available' => true,
        ]);

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addDay()->endOfDay();

        $event = Event::factory()->create([
            'start_time' => $startDate->copy()->addHour()->toDateTimeString(),
            'end_time' => $startDate->copy()->addHours(2)->toDateTimeString(),
        ]);

        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->toDateString(),
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
        ]);

        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $shiftQualification->id,
        ]);

        $service = app()->make(EventService::class);

        $daysWithData = $service->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'user',
            $startDate,
            $endDate
        );

        $this->assertArrayHasKey($startDate->toDateString(), $daysWithData);
        $this->assertNotEmpty($daysWithData[$startDate->toDateString()]['shifts']);
    }
}
