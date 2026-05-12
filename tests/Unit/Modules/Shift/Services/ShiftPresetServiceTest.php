<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftPreset;
use Artwork\Modules\Shift\Services\ShiftPresetService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftPresetServiceTest extends TestCase
{
    private ShiftPresetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftPresetService::class);
    }

    #[Test]
    public function create_from_request_persists_shift_preset(): void
    {
        $request = new Request(['name' => 'Standard Show']);

        $preset = $this->service->createFromRequest($request);

        $this->assertNotNull($preset->id);
        $this->assertSame('Standard Show', $preset->name);
        $this->assertDatabaseHas('shift_presets', ['id' => $preset->id, 'name' => 'Standard Show']);
    }

    #[Test]
    public function update_from_request_changes_name(): void
    {
        $preset = ShiftPreset::factory()->create(['name' => 'Old']);

        $request = new Request(['name' => 'Renamed']);

        $updated = $this->service->updateFromRequest($preset, $request);

        $this->assertSame('Renamed', $updated->name);
        $this->assertDatabaseHas('shift_presets', ['id' => $preset->id, 'name' => 'Renamed']);
    }

    #[Test]
    public function delete_removes_shift_preset(): void
    {
        $preset = ShiftPreset::factory()->create();

        $this->service->delete($preset);

        $this->assertDatabaseMissing('shift_presets', ['id' => $preset->id]);
    }
}
