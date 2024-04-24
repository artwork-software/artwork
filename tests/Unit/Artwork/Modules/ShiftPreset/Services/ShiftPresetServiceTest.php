<?php

namespace Tests\Unit\Artwork\Modules\ShiftPreset\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\PresetShift\Services\PresetShiftService;
use Artwork\Modules\PresetShift\Services\PresetShiftsShiftsQualificationsService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftPreset\Services\ShiftPresetService;
use Artwork\Modules\ShiftPresetTimeline\Services\ShiftPresetTimelineService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ShiftPresetServiceTest extends TestCase
{
    private ShiftPresetService $shiftPresetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shiftPresetService = $this->app->make(ShiftPresetService::class);
    }

    public function testGetAllShiftPresetsWithSortedTimelines(): void
    {
        $shiftPresets = ShiftPreset::factory()->count(3)->create();

        $result = $this->shiftPresetService->getAllShiftPresetsWithSortedTimelines();

        $this->assertCount(3, $result);
        $this->assertEquals($shiftPresets->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function testFindByNameAndEventTypeId(): void
    {
        $shiftPreset = ShiftPreset::factory()->create();

        $result = $this->shiftPresetService->findByNameAndEventTypeId($shiftPreset->name, $shiftPreset->event_type_id);

        $this->assertCount(1, $result);
        $this->assertEquals($shiftPreset->id, $result->first()->id);
    }

    public function testStoreFromEventAndRequest(): void
    {
        $event = Event::factory()->create();
        $request = new Request([
            'name' => 'Test Shift Preset',
            'event_type_id' => $event->event_type_id
        ]);

        $this->shiftPresetService->storeFromEventAndRequest(
            $event,
            $request,
            app()->make(PresetShiftService::class),
            app()->make(PresetShiftsShiftsQualificationsService::class),
            app()->make(ShiftPresetTimelineService::class)
        );

        $this->assertDatabaseHas('shift_presets', [
            'name' => 'Test Shift Preset',
            'event_type_id' => $event->event_type_id
        ]);
    }

    public function testDuplicateShiftPreset(): void
    {
        $shiftPreset = ShiftPreset::factory()->create();

        $this->shiftPresetService->duplicateShiftPreset(
            $shiftPreset,
            app()->make(PresetShiftService::class),
            app()->make(PresetShiftsShiftsQualificationsService::class),
            app()->make(ShiftPresetTimelineService::class)
        );

        $this->assertDatabaseCount('shift_presets', 2);
    }

    public function testCreateFromRequest(): void
    {
        $request = new Request([
            'name' => 'Test Shift Preset',
            'event_type_id' => 1
        ]);

        $shiftPreset = $this->shiftPresetService->createFromRequest($request);

        $this->assertDatabaseHas('shift_presets', [
            'name' => 'Test Shift Preset',
            'event_type_id' => 1
        ]);
    }

    public function testUpdateFromRequest(): void
    {
        $shiftPreset = ShiftPreset::factory()->create();
        $request = new Request([
            'name' => 'Updated Shift Preset',
            'event_type_id' => $shiftPreset->event_type_id
        ]);

        $updatedShiftPreset = $this->shiftPresetService->updateFromRequest($shiftPreset, $request);

        $this->assertEquals('Updated Shift Preset', $updatedShiftPreset->name);
    }

    public function testDelete(): void
    {
        $shiftPreset = ShiftPreset::factory()->create();

        $this->assertDatabaseHas('shift_presets', ['id' => $shiftPreset->id]);
        $this->shiftPresetService->delete($shiftPreset);

        $this->assertDatabaseMissing('shift_presets', ['id' => $shiftPreset->id]);
    }
}
