<?php

namespace Tests\Unit\Modules\Shift\Services;

use App\Settings\ShiftSettings;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftOverbookingTest extends TestCase
{
    private ShiftWorkerService $shiftWorkerService;

    private ShiftsQualificationsService $shiftsQualificationsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shiftWorkerService = app(ShiftWorkerService::class);
        $this->shiftsQualificationsService = app(ShiftsQualificationsService::class);
    }

    private function enableOverbooking(bool $enabled = true): void
    {
        $settings = app(ShiftSettings::class);
        $settings->allow_shift_overbooking = $enabled;
        $settings->save();
    }

    #[Test]
    public function overbooked_assignment_sets_flag_and_keeps_demand_unchanged(): void
    {
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
        ]);

        $regularWorker = User::factory()->create();
        $overbookedWorker = User::factory()->create();

        $this->shiftWorkerService->assignToShift($shift, $regularWorker, $qualification->id, 'TST');
        $this->shiftWorkerService->assignToShift(
            $shift,
            $overbookedWorker,
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            null,
            true
        );

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $overbookedWorker->id,
            'is_overbooked' => true,
        ]);
        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $regularWorker->id,
            'is_overbooked' => false,
        ]);
        // Bedarf bleibt 1 ("2/1"), Überbuchungsplatz wurde implizit angelegt
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
            'overbooked_value' => 1,
        ]);
    }

    #[Test]
    public function overbooked_assignment_throws_when_setting_disabled(): void
    {
        $this->enableOverbooking(false);

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $worker = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->shiftWorkerService->assignToShift(
            $shift,
            $worker,
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            null,
            true
        );
    }

    #[Test]
    public function normal_assignment_beyond_demand_still_increases_demand(): void
    {
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
        ]);

        $workerOne = User::factory()->create();
        $workerTwo = User::factory()->create();

        $this->shiftWorkerService->assignToShift($shift, $workerOne, $qualification->id, 'TST');
        $this->shiftWorkerService->assignToShift($shift, $workerTwo, $qualification->id, 'TST');

        // Bisheriges Verhalten unverändert: Bedarf wird still auf die Worker-Anzahl angehoben
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
            'overbooked_value' => 0,
        ]);
    }

    #[Test]
    public function overbooked_assignment_uses_existing_open_slot_without_increasing_it(): void
    {
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
            'overbooked_value' => 1,
        ]);

        $worker = User::factory()->create();

        $this->shiftWorkerService->assignToShift(
            $shift,
            $worker,
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            null,
            true
        );

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
            'overbooked_value' => 1,
        ]);
    }

    #[Test]
    public function increase_overbooked_value_creates_entry_with_zero_demand_when_missing(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $this->shiftsQualificationsService->increaseOverbookedValue($shift->id, $qualification->id);

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 0,
            'overbooked_value' => 1,
        ]);
    }

    #[Test]
    public function increase_overbooked_value_increments_existing_entry(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);

        $this->shiftsQualificationsService->increaseOverbookedValue($shift->id, $qualification->id);

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
            'overbooked_value' => 1,
        ]);
    }

    #[Test]
    public function decrease_overbooked_value_only_removes_open_slots(): void
    {
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
            'overbooked_value' => 2,
        ]);

        $worker = User::factory()->create();
        $this->shiftWorkerService->assignToShift(
            $shift,
            $worker,
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            null,
            true
        );

        // Ein offener + ein befüllter Überbuchungsplatz: erster Decrease entfernt den offenen ...
        $this->shiftsQualificationsService->decreaseOverbookedValue($shift->id, $qualification->id);
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'overbooked_value' => 1,
        ]);

        // ... der zweite greift nicht, weil der verbleibende Platz befüllt ist
        $this->shiftsQualificationsService->decreaseOverbookedValue($shift->id, $qualification->id);
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'overbooked_value' => 1,
        ]);
    }
}
