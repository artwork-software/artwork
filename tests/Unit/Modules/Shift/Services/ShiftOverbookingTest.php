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

    // ---------- Welle 3: B15/B16/B18/B19/B33 ----------

    #[Test]
    public function overbooked_workers_do_not_inflate_demand_on_regular_assignment(): void
    {
        // B15: Bedarf 2, davon 1 regulär + 1 überbucht besetzt. Eine weitere reguläre
        // Zuweisung füllt den zweiten regulären Platz — value muss 2 bleiben
        // (vorher wurden Überbuchte mitgezählt und value auf 3 hochgesetzt).
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);

        $this->shiftWorkerService->assignToShift($shift, User::factory()->create(), $qualification->id, 'TST');
        // Offenen Überbuchungsplatz anlegen, damit die Überbuchungs-Zuweisung
        // trotz freiem regulären Platz als Überbuchung gewertet wird
        $this->shiftsQualificationsService->increaseOverbookedValue($shift->id, $qualification->id);
        $this->shiftWorkerService->assignToShift(
            $shift,
            User::factory()->create(),
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            null,
            true
        );
        $this->shiftWorkerService->assignToShift($shift, User::factory()->create(), $qualification->id, 'TST');

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);
    }

    #[Test]
    public function demand_cannot_be_lowered_below_regular_worker_count(): void
    {
        // B33: 2 reguläre Worker zugewiesen — value=1 würde Überbesetzung ohne
        // Überbuchungs-Markierung erzeugen, daher Clamp auf 2.
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 3,
        ]);
        $this->shiftWorkerService->assignToShift($shift, User::factory()->create(), $qualification->id, 'TST');
        $this->shiftWorkerService->assignToShift($shift, User::factory()->create(), $qualification->id, 'TST');

        $this->shiftsQualificationsService->updateShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
        ]);

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);
    }

    #[Test]
    public function stale_overbooked_flag_becomes_regular_assignment_when_slot_is_free(): void
    {
        // B18: isOverbooked=true bei freiem regulären Platz und OHNE offenen
        // Überbuchungsplatz (veralteter Frontend-State) → reguläre Zuweisung,
        // kein neuer Überbuchungsplatz.
        $this->enableOverbooking();

        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
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

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $worker->id,
            'is_overbooked' => false,
        ]);
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
            'overbooked_value' => 0,
        ]);
    }

    #[Test]
    public function series_assignment_fills_free_regular_slot_even_with_overbooked_worker_present(): void
    {
        // B16: Serien-Schicht mit value=2, 1 regulär + 1 überbucht besetzt → galt
        // vorher als "voll" (Überbuchte mitgezählt) und wurde übersprungen.
        $this->enableOverbooking();

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $qualification = ShiftQualification::factory()->create();

        $sourceShift = $this->createSeriesShift($uuid, '2026-07-06', $qualification->id, 2);
        $targetShift = $this->createSeriesShift($uuid, '2026-07-07', $qualification->id, 2);

        // Ziel-Schicht: 1 regulärer + 1 überbuchter Worker → 1 regulärer Platz frei.
        // Der offene Überbuchungsplatz wird explizit angelegt, damit die
        // Überbuchungs-Zuweisung trotz freiem regulären Platz als Überbuchung zählt.
        $this->shiftWorkerService->assignToShift($targetShift, User::factory()->create(), $qualification->id, 'TST');
        $this->shiftsQualificationsService->increaseOverbookedValue($targetShift->id, $qualification->id);
        $this->shiftWorkerService->assignToShift(
            $targetShift,
            User::factory()->create(),
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            null,
            true
        );

        $seriesWorker = User::factory()->create();
        $this->shiftWorkerService->handleSeriesShiftData(
            $sourceShift,
            \Carbon\Carbon::parse('2026-07-01')->startOfDay(),
            \Carbon\Carbon::parse('2026-07-31')->endOfDay(),
            'all',
            $seriesWorker,
            $qualification->id,
            'TST'
        );

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $targetShift->id,
            'employable_id' => $seriesWorker->id,
            'is_overbooked' => false,
        ]);
    }

    #[Test]
    public function series_assignment_fills_pure_overbooking_function(): void
    {
        // B19: per Overbook-Endpoint angelegte Funktion (value=0, overbooked_value=1)
        // wurde von der Serien-Zuweisung mit isOverbooked=true übersprungen.
        $this->enableOverbooking();

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $qualification = ShiftQualification::factory()->create();

        $sourceShift = $this->createSeriesShift($uuid, '2026-07-06', $qualification->id, 1);
        $targetShift = $this->createSeriesShift($uuid, '2026-07-07', $qualification->id, 0, 1);

        $seriesWorker = User::factory()->create();
        $this->shiftWorkerService->handleSeriesShiftData(
            $sourceShift,
            \Carbon\Carbon::parse('2026-07-01')->startOfDay(),
            \Carbon\Carbon::parse('2026-07-31')->endOfDay(),
            'all',
            $seriesWorker,
            $qualification->id,
            'TST',
            null,
            null,
            null,
            null,
            true
        );

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $targetShift->id,
            'employable_id' => $seriesWorker->id,
            'is_overbooked' => true,
        ]);
        // Offener Platz wurde genutzt, nicht erhöht
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $targetShift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 0,
            'overbooked_value' => 1,
        ]);
    }

    private function createSeriesShift(
        string $uuid,
        string $date,
        int $qualificationId,
        int $value,
        int $overbookedValue = 0
    ): Shift {
        $shift = Shift::factory()->create([
            'shift_uuid' => $uuid,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '09:00:00',
            'end' => '17:00:00',
        ]);
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => $qualificationId,
            'value' => $value,
            'overbooked_value' => $overbookedValue,
        ]);

        return $shift;
    }
}
