<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Artwork\Modules\Shift\Services\PresetShiftService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PresetShiftServiceTest extends TestCase
{
    private PresetShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PresetShiftService::class);
    }

    #[Test]
    public function create_from_request_for_shift_preset_returns_id(): void
    {
        $craft = Craft::factory()->create();
        $shiftPreset = ShiftPreset::factory()->create();

        $request = new Request([
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'craft_id' => $craft->id,
            'description' => 'Morning shift preset',
        ]);

        $presetShiftId = $this->service->createFromRequestForShiftPreset($shiftPreset->id, $request);

        $this->assertIsInt($presetShiftId);
        $this->assertDatabaseHas('preset_shifts', [
            'id' => $presetShiftId,
            'description' => 'Morning shift preset',
            'break_minutes' => 30,
        ]);
    }

    #[Test]
    public function update_from_request_changes_values(): void
    {
        $craft = Craft::factory()->create();
        $shiftPreset = ShiftPreset::factory()->create();
        $presetShift = PresetShift::factory()->create([
            'shift_preset_id' => $shiftPreset->id,
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'craft_id' => $craft->id,
            'description' => 'Old',
        ]);

        $request = new Request([
            'start' => '09:00',
            'end' => '18:00',
            'break_minutes' => 45,
            'craft_id' => $craft->id,
            'description' => 'New',
        ]);

        $this->service->updateFromRequest($presetShift, $request);

        $presetShift->refresh();
        $this->assertSame('09:00', (string) $presetShift->start);
        $this->assertSame('18:00', (string) $presetShift->end);
        $this->assertSame(45, (int) $presetShift->break_minutes);
        $this->assertSame('New', $presetShift->description);
    }

    #[Test]
    public function create_preset_shift_from_existing_shift_clones_attributes(): void
    {
        $craft = Craft::factory()->create();
        $shift = Shift::factory()->create([
            'start' => '10:00',
            'end' => '14:00',
            'break_minutes' => 15,
            'craft_id' => $craft->id,
            'description' => 'Hello',
        ]);

        $shiftPreset = ShiftPreset::factory()->create();
        $presetShift = $this->service->createPresetShiftFromExistingShift($shiftPreset->id, $shift);

        $this->assertNotNull($presetShift->id);
        $this->assertSame($shiftPreset->id, $presetShift->shift_preset_id);
        $this->assertSame(15, (int) $presetShift->break_minutes);
        $this->assertSame('Hello', $presetShift->description);
    }

    #[Test]
    public function update_description_persists_new_value(): void
    {
        $presetShift = PresetShift::factory()->create(['description' => 'Old']);

        $result = $this->service->updateDescription('Updated', $presetShift);

        $this->assertSame('Updated', $result->description);
        $this->assertDatabaseHas('preset_shifts', [
            'id' => $presetShift->id,
            'description' => 'Updated',
        ]);
    }
}
