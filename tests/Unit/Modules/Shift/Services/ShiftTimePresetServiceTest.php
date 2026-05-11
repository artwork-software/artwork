<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftTimePreset;
use Artwork\Modules\Shift\Services\ShiftTimePresetService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftTimePresetServiceTest extends TestCase
{
    private ShiftTimePresetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftTimePresetService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        ShiftTimePreset::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    #[Test]
    public function create_by_request_persists_record(): void
    {
        $this->service->createByRequest([
            'name' => 'Morning',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'break_time' => 45,
        ]);

        $this->assertDatabaseHas('shift_time_presets', [
            'name' => 'Morning',
            'break_time' => 45,
        ]);
    }

    #[Test]
    public function update_by_request_persists_changes(): void
    {
        $preset = ShiftTimePreset::factory()->create(['name' => 'Old']);

        $this->service->updateByRequest($preset, [
            'name' => 'Renamed',
            'start_time' => '10:00',
            'end_time' => '18:00',
            'break_time' => 60,
        ]);

        $this->assertDatabaseHas('shift_time_presets', [
            'id' => $preset->id,
            'name' => 'Renamed',
            'break_time' => 60,
        ]);
    }

    #[Test]
    public function delete_removes_preset(): void
    {
        $preset = ShiftTimePreset::factory()->create();

        $this->service->delete($preset);

        $this->assertDatabaseMissing('shift_time_presets', ['id' => $preset->id]);
    }
}
