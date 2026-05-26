<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftsQualificationsServiceTest extends TestCase
{
    private ShiftsQualificationsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftsQualificationsService::class);
    }

    #[Test]
    public function create_shifts_qualification_for_shift_persists_new_row(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $this->service->createShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);
    }

    #[Test]
    public function update_shifts_qualification_creates_new_if_missing(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $this->service->updateShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'value' => 3,
        ]);

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 3,
        ]);
    }

    #[Test]
    public function update_shifts_qualification_updates_existing(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $existing = ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
        ]);

        $this->service->updateShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'value' => 7,
        ]);

        $this->assertDatabaseHas('shifts_qualifications', [
            'id' => $existing->id,
            'value' => 7,
        ]);
    }

    #[Test]
    public function delete_soft_deletes_shifts_qualification(): void
    {
        $sq = ShiftsQualifications::factory()->create();

        $this->service->delete($sq);

        $this->assertSoftDeleted('shifts_qualifications', ['id' => $sq->id]);
    }

    #[Test]
    public function force_delete_removes_shifts_qualification(): void
    {
        $sq = ShiftsQualifications::factory()->create();

        $this->service->forceDelete($sq);

        $this->assertDatabaseMissing('shifts_qualifications', ['id' => $sq->id]);
    }

    #[Test]
    public function find_all_by_shift_qualification_id_returns_matching_rows(): void
    {
        $qualification = ShiftQualification::factory()->create();
        ShiftsQualifications::factory()->count(2)->create([
            'shift_qualification_id' => $qualification->id,
        ]);
        ShiftsQualifications::factory()->create();

        $result = $this->service->findAllByShiftQualificationId($qualification->id);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function increase_value_or_create_with_one_by_qualification_creates_new_with_value_one(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $this->service->increaseValueOrCreateWithOneByQualification($shift->id, $qualification->id);

        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
        ]);
    }

    #[Test]
    public function increase_value_or_create_with_one_by_qualification_increments_existing(): void
    {
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $existing = ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 4,
        ]);

        $this->service->increaseValueOrCreateWithOneByQualification($shift->id, $qualification->id);

        $this->assertDatabaseHas('shifts_qualifications', [
            'id' => $existing->id,
            'value' => 5,
        ]);
    }
}
