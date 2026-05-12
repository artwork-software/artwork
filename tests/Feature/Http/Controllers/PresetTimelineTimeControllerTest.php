<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetTimelineTime;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class PresetTimelineTimeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_destroy(): void
    {
        $timeline = ShiftPresetTimeline::query()->forceCreate(['name' => 'X']);
        $time = PresetTimelineTime::query()->forceCreate([
            'preset_timeline_id' => $timeline->id,
            'start' => '08:00',
            'end' => '16:00',
            'description' => 'desc',
        ]);
        $this->delete(route('timeline-preset.time.destroy', $time))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $timeline = ShiftPresetTimeline::query()->forceCreate(['name' => 'X']);
        $time = PresetTimelineTime::query()->forceCreate([
            'preset_timeline_id' => $timeline->id,
            'start' => '08:00',
            'end' => '16:00',
            'description' => 'desc',
        ]);

        $response = $this->delete(route('timeline-preset.time.destroy', $time));

        $response->assertOk();
    }
}
