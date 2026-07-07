<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftWorkerAvailability;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Eingeplant, aber nicht verfügbar: Personen bleiben (insb. bei festgeschriebenen
 * Schichten) zugewiesen, der Verfügbarkeitskonflikt wird aber als is_unavailable
 * an den Schichtplan gemeldet (Warndreieck statt "voll besetzt").
 */
final class ShiftWorkerUnavailabilityTest extends FeatureTestCase
{
    private function createCommittedShiftWithUser(): array
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => true,
            // Festgeschriebene Schichten haben in Produktion immer eine*n Festschreibende*n;
            // VacationConflictService verlangt das (user_name NOT NULL)
            'committing_user_id' => User::factory()->create()->id,
        ]);
        $qualification = ShiftQualification::factory()->create();
        $user = User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        return [$shift->fresh(), $user];
    }

    private function loadedWorker(Shift $shift): User
    {
        $shift->load('users');

        return $shift->users->first();
    }

    #[Test]
    public function full_day_not_available_entry_marks_worker_as_unavailable(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-20',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $this->assertTrue(
            ShiftWorkerAvailability::isWorkerUnavailable($shift, $this->loadedWorker($shift))
        );
    }

    #[Test]
    public function available_entry_does_not_mark_worker_as_unavailable(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-20',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::AVAILABLE,
        ]);

        $this->assertFalse(
            ShiftWorkerAvailability::isWorkerUnavailable($shift, $this->loadedWorker($shift))
        );
    }

    #[Test]
    public function vacation_on_other_day_does_not_mark_worker_as_unavailable(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-21',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $this->assertFalse(
            ShiftWorkerAvailability::isWorkerUnavailable($shift, $this->loadedWorker($shift))
        );
    }

    #[Test]
    public function partial_day_entry_only_conflicts_when_times_overlap_shift(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();

        // 06:00-09:00 liegt vor der Schicht (10:00-18:00) → kein Konflikt
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-20',
            'full_day' => false,
            'start_time' => '06:00',
            'end_time' => '09:00',
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $this->assertFalse(
            ShiftWorkerAvailability::isWorkerUnavailable($shift, $this->loadedWorker($shift))
        );

        // 16:00-20:00 überlappt die Schicht → Konflikt
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-20',
            'full_day' => false,
            'start_time' => '16:00',
            'end_time' => '20:00',
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $this->assertTrue(
            ShiftWorkerAvailability::isWorkerUnavailable($shift, $this->loadedWorker($shift->fresh()))
        );
    }

    #[Test]
    public function shift_dto_serializes_is_unavailable_flag_for_workers(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-20',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $shift->load(['users', 'shiftsQualifications', 'globalQualifications']);
        $dto = ShiftDTO::fromModel($shift);

        $this->assertCount(1, $dto->workers);
        $this->assertTrue($dto->workers[0]['is_unavailable']);
    }

    #[Test]
    public function worker_without_vacations_is_not_unavailable(): void
    {
        [$shift] = $this->createCommittedShiftWithUser();

        $this->assertFalse(
            ShiftWorkerAvailability::isWorkerUnavailable($shift, $this->loadedWorker($shift))
        );
    }

    #[Test]
    public function check_vacation_never_detaches_committed_shifts(): void
    {
        $this->actingAsAdmin();

        [$committedShift, $user] = $this->createCommittedShiftWithUser();
        $committedShift->update(['event_start_day' => '2026-07-20']);

        $uncommittedShift = Shift::factory()->create([
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'event_start_day' => '2026-07-20',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => false,
        ]);
        $qualification = ShiftQualification::factory()->create();
        $uncommittedShift->users()->attach($user->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        $this->patch(route('user.check.vacation', ['user' => $user->id]), [
            'day' => '2026-07-20',
            'checked' => ['type' => 'NOT_AVAILABLE', 'name' => 'Nicht Verfügbar'],
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE', 'name' => 'Verfügbar'],
            'remove_from_shifts' => true,
        ])->assertSuccessful();

        // Festgeschriebene Schicht: Zuweisung bleibt (Stundenabrechnung)
        $this->assertTrue($committedShift->fresh()->users()->where('users.id', $user->id)->exists());
        // Nicht festgeschriebene Schicht: bisheriges Detach-Verhalten bleibt
        $this->assertFalse($uncommittedShift->fresh()->users()->where('users.id', $user->id)->exists());
    }

    #[Test]
    public function check_vacation_from_shift_plan_modal_keeps_all_assignments(): void
    {
        $this->actingAsAdmin();

        [$committedShift, $user] = $this->createCommittedShiftWithUser();
        $committedShift->update(['event_start_day' => '2026-07-20']);

        $this->patch(route('user.check.vacation', ['user' => $user->id]), [
            'day' => '2026-07-20',
            'checked' => ['type' => 'NOT_AVAILABLE', 'name' => 'Nicht Verfügbar'],
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE', 'name' => 'Verfügbar'],
            'remove_from_shifts' => false,
        ])->assertSuccessful();

        $this->assertTrue($committedShift->fresh()->users()->where('users.id', $user->id)->exists());
        // Der Konflikt ist anschließend im DTO sichtbar
        $fresh = $committedShift->fresh();
        $this->assertTrue(
            ShiftWorkerAvailability::isWorkerUnavailable($fresh, $this->loadedWorker($fresh))
        );
    }
}
