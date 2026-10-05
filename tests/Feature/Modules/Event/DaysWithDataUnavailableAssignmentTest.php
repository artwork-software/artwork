<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Im Einsatzplan war ein Abwesenheitseintrag am Schichttag bisher nur unten in der
 * Liste sichtbar — der Tag selbst sah aus wie jeder andere. daysWithData meldet den
 * Konflikt deshalb pro Tag, damit die Tagesspalte markiert werden kann.
 */
final class DaysWithDataUnavailableAssignmentTest extends FeatureTestCase
{
    private function attachUser(Shift $shift, User $user): void
    {
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function daysWithData(User $user, string $start, string $end): array
    {
        return app(EventService::class)->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'user',
            Carbon::parse($start),
            Carbon::parse($end)
        );
    }

    private function shiftOn(string $date, bool $committed = false): Shift
    {
        return Shift::factory()->create([
            'event_id' => null,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '09:00:00',
            'end' => '17:30:00',
            'is_committed' => $committed,
        ]);
    }

    private function vacationFor(User $user, string $date, VacationType $type): void
    {
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => $date,
            'full_day' => true,
            'type' => $type->value,
        ]);
    }

    #[Test]
    public function day_with_shift_and_absence_is_flagged(): void
    {
        $user = User::factory()->create();
        $shift = $this->shiftOn('2026-09-10', committed: true);
        $this->attachUser($shift, $user);
        $this->vacationFor($user, '2026-09-10', VacationType::NOT_AVAILABLE);

        $days = $this->daysWithData($user, '2026-09-07', '2026-09-13');

        $this->assertSame(
            ['status' => VacationType::NOT_AVAILABLE->value, 'committed' => true],
            $days['2026-09-10']['unavailableAssignment']
        );
        $this->assertSame(
            VacationType::NOT_AVAILABLE->value,
            $days['2026-09-10']['shifts'][0]['worker_unavailable_status']
        );
    }

    #[Test]
    public function day_without_absence_is_not_flagged(): void
    {
        $user = User::factory()->create();
        $shift = $this->shiftOn('2026-09-10');
        $this->attachUser($shift, $user);

        $days = $this->daysWithData($user, '2026-09-07', '2026-09-13');

        $this->assertNull($days['2026-09-10']['unavailableAssignment']);
        $this->assertNull($days['2026-09-10']['shifts'][0]['worker_unavailable_status']);
    }

    #[Test]
    public function available_entry_does_not_flag_the_day(): void
    {
        $user = User::factory()->create();
        $shift = $this->shiftOn('2026-09-10');
        $this->attachUser($shift, $user);
        $this->vacationFor($user, '2026-09-10', VacationType::AVAILABLE);

        $days = $this->daysWithData($user, '2026-09-07', '2026-09-13');

        $this->assertNull($days['2026-09-10']['unavailableAssignment']);
    }

    /**
     * Die Abwesenheiten werden einmal für den Zeitraum geladen — vorher hätte ein
     * lazy Nachladen die Relation auf das Fenster der ersten Schicht festgenagelt
     * und jeden weiteren Tag falsch bewertet.
     */
    #[Test]
    public function each_day_of_the_period_is_evaluated_separately(): void
    {
        $user = User::factory()->create();
        $this->attachUser($this->shiftOn('2026-09-08'), $user);
        $this->attachUser($this->shiftOn('2026-09-10'), $user);
        $this->vacationFor($user, '2026-09-10', VacationType::OFF_WORK);

        $days = $this->daysWithData($user, '2026-09-07', '2026-09-13');

        $this->assertNull($days['2026-09-08']['unavailableAssignment']);
        $this->assertSame(
            ['status' => VacationType::OFF_WORK->value, 'committed' => false],
            $days['2026-09-10']['unavailableAssignment']
        );
    }
}
