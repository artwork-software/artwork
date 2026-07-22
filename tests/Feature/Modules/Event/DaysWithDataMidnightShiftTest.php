<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regression: Schichten, die bis oder über 0 Uhr hinausgehen, fielen aus der
 * Gesamtstundenanzahl im Profil (daysWithData.totalWorkTime) heraus, weil
 * start/end als reine Uhrzeiten verglichen wurden (negative Differenz -> 0).
 */
final class DaysWithDataMidnightShiftTest extends FeatureTestCase
{
    private function attachUser(Shift $shift, User $user): void
    {
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);
    }

    private function daysWithData(User $user, string $start, string $end): array
    {
        return app(EventService::class)->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'user',
            Carbon::parse($start),
            Carbon::parse($end)
        );
    }

    #[Test]
    public function shift_over_midnight_is_counted_in_total_work_time(): void
    {
        $user = User::factory()->create();

        // 20:00 - 02:00 (Folgetag), 30 Min Pause -> 5,5h am Starttag
        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-13',
            'start' => '20:00:00',
            'end' => '02:00:00',
            'break_minutes' => 30,
        ]);
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-08-10', '2026-08-16');

        $this->assertSame('05:30', $days['2026-08-12']['totalWorkTime']);
        $this->assertCount(1, $days['2026-08-12']['shifts']);
    }

    #[Test]
    public function shift_ending_exactly_at_midnight_is_counted(): void
    {
        $user = User::factory()->create();

        // 18:00 - 00:00 (Folgetag) -> 6h
        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-13',
            'start' => '18:00:00',
            'end' => '00:00:00',
            'break_minutes' => 0,
        ]);
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-08-10', '2026-08-16');

        $this->assertSame('06:00', $days['2026-08-12']['totalWorkTime']);
    }

    #[Test]
    public function midnight_shift_on_last_day_of_range_is_not_dropped(): void
    {
        $user = User::factory()->create();

        // end_date liegt hinter dem angefragten Zeitraum -> Schicht zählt
        // trotzdem zum Starttag (letzter Tag des Zeitraums)
        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-17',
            'start' => '22:00:00',
            'end' => '02:00:00',
            'break_minutes' => 0,
        ]);
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-08-10', '2026-08-16');

        $this->assertSame('04:00', $days['2026-08-16']['totalWorkTime']);
    }

    #[Test]
    public function event_shift_over_midnight_is_counted(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'start_time' => '2026-08-12 20:00:00',
            'end_time' => '2026-08-13 02:00:00',
        ]);

        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-13',
            'start' => '20:00:00',
            'end' => '02:00:00',
            'break_minutes' => 60,
        ]);
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-08-10', '2026-08-16');

        $this->assertSame('05:00', $days['2026-08-12']['totalWorkTime']);
    }

    #[Test]
    public function event_over_midnight_on_last_day_of_range_is_not_dropped(): void
    {
        $user = User::factory()->create();

        // Termin läuft über das Zeitraum-Ende hinaus -> Schicht am letzten Tag
        // darf nicht herausfallen
        $event = Event::factory()->create([
            'start_time' => '2026-08-16 22:00:00',
            'end_time' => '2026-08-17 02:00:00',
        ]);

        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-17',
            'start' => '22:00:00',
            'end' => '02:00:00',
            'break_minutes' => 0,
        ]);
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-08-10', '2026-08-16');

        $this->assertSame('04:00', $days['2026-08-16']['totalWorkTime']);
    }

    #[Test]
    public function normal_day_shift_still_counted_correctly(): void
    {
        $user = User::factory()->create();

        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'start' => '08:00:00',
            'end' => '16:30:00',
            'break_minutes' => 30,
        ]);
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-08-10', '2026-08-16');

        $this->assertSame('08:00', $days['2026-08-12']['totalWorkTime']);
    }
}
