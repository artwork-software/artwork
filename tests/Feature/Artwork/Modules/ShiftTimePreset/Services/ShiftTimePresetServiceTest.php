<?php

namespace Tests\Feature\Artwork\Modules\ShiftTimePreset\Services;

use Artwork\Modules\ShiftTimePreset\Models\ShiftTimePreset;
use Artwork\Modules\ShiftTimePreset\Services\ShiftTimePresetService;
use Tests\TestCase;

class ShiftTimePresetServiceTest extends TestCase
{
    private readonly ShiftTimePresetService $shiftTimePresetService;
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->shiftTimePresetService = $this->app->make(ShiftTimePresetService::class);
    }

    public function testCreateByRequest(): void
    {
        $data = ShiftTimePreset::factory()->make()->toArray();
        $this->shiftTimePresetService->createByRequest($data);
        $this->assertDatabaseHas('shift_time_presets', $data);
    }

    public function testUpdateByRequest(): void
    {
        $shiftTimePreset = ShiftTimePreset::factory()->create();
        $data = ShiftTimePreset::factory()->make()->toArray();
        $this->shiftTimePresetService->updateByRequest($shiftTimePreset, $data);
        $this->assertDatabaseHas('shift_time_presets', $data);
    }

    public function testDelete(): void
    {
        $shiftTimePreset = ShiftTimePreset::factory()->create();
        $this->shiftTimePresetService->delete($shiftTimePreset);
        $this->assertDatabaseMissing('shift_time_presets', $shiftTimePreset->toArray());
    }
}