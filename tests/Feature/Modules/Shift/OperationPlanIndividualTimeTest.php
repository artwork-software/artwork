<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regression: Der Einsatzplan zeigte in der Karte die individuelle Arbeitszeit, rechnete Tages-/Wochen-
 * summen und die Zeitzeile darüber aber mit der Schichtzeit.
 */
final class OperationPlanIndividualTimeTest extends FeatureTestCase
{
    #[Test]
    public function operation_plan_uses_the_individual_time_of_the_person(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create();
        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '18:00:00',
            'break_minutes' => 0,
        ]);
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'craft_abbreviation' => 'X',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start_time' => '12:00',
            'end_time' => '14:00',
        ]);

        $days = app(EventService::class)->getDaysWithEventsAndTotalPlannedWorkingHours(
            $worker->id,
            'user',
            Carbon::parse('2026-06-08'),
            Carbon::parse('2026-06-08'),
        );

        $day = $days['2026-06-08'];
        $this->assertSame('02:00', $day['totalWorkTime']);
        $this->assertSame('12:00', $day['shifts'][0]['worker_start']);
        $this->assertSame('14:00', $day['shifts'][0]['worker_end']);
        $this->assertSame('02:00', $day['shifts'][0]['plannedWorkingHours']);
    }
}
