<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class TimelinePresetControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('shifts.timeline-presets.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.timeline-presets.index'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('timeline-presets.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('timeline-presets.store'), [
            'name' => 'My Preset',
            'dataset' => [
                ['start' => '08:00', 'end' => '16:00', 'description' => 'desc'],
            ],
        ]);

        $response->assertOk();
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $preset = ShiftPresetTimeline::query()->forceCreate([
            'name' => 'X',
        ]);

        $response = $this->delete(route('timeline-preset.destroy', $preset));

        $response->assertOk();
        $this->assertDatabaseMissing('shift_preset_timelines', ['id' => $preset->id]);
    }
}
