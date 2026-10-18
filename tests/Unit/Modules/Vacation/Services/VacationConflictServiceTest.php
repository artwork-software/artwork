<?php

namespace Tests\Unit\Modules\Vacation\Services;

use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VacationConflictServiceTest extends TestCase
{
    private VacationConflictService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VacationConflictService::class);
    }

    #[Test]
    public function create_persists_vacation_conflict(): void
    {
        $existing = VacationConflict::factory()->create();

        $conflict = $this->service->create([
            'vacation_id' => $existing->vacation_id,
            'shift_id' => $existing->shift_id,
            'user_name' => 'Tester',
            'date' => '2025-04-01',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertInstanceOf(VacationConflict::class, $conflict);
        $this->assertTrue($conflict->exists);
        $this->assertSame('Tester', $conflict->user_name);
    }

    #[Test]
    public function check_vacation_conflicts_on_day_returns_void_when_no_user_or_freelancer(): void
    {
        // Should not throw when neither user nor freelancer is provided
        $this->service->checkVacationConflictsOnDay(
            '2025-01-01',
            null,
            null,
            app(NotificationService::class)
        );

        $this->assertTrue(true);
    }

    #[Test]
    public function a_conflict_with_an_unknown_scheduler_is_recorded_instead_of_failing(): void
    {
        $this->actingAs(User::factory()->create());
        $worker = User::factory()->create()->fresh();
        $day = '2026-11-12';
        $shift = Shift::factory()->create([
            'event_id' => null, 'start_date' => $day, 'end_date' => $day, 'committing_user_id' => null,
        ]);
        // Zuweisung ohne erfasste einteilende Person (Altbestand)
        $shift->users()->attach($worker->id, ['shift_qualification_id' => ShiftQualification::factory()->create()->id]);
        \Artwork\Modules\Shift\Models\ShiftWorker::query()->where('shift_id', $shift->id)->update(['assigned_by_user_id' => null]);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => $day,
            // Factory würfelt sonst „ganztägig“ – ohne Uhrzeit überschneidet sich nichts (wackelnder Test)
            'full_day' => true,
            'is_series' => false,
        ]);

        // vorher: user_name NOT NULL → QueryException, Abwesenheit eintragen endete mit 500
        $this->service->checkVacationConflictsShifts($shift->fresh(), app(NotificationService::class), $worker);

        $conflict = VacationConflict::query()->where('shift_id', $shift->id)->sole();
        $this->assertNull($conflict->user_name);
    }
}
