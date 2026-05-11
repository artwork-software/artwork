<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\ShiftTimePreset;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftTimePresetControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('shift-time-preset.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('shift-time-preset.store'), [
            'name' => 'Morning',
            'break_time' => 30,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('shift_time_presets', ['name' => 'Morning']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $preset = ShiftTimePreset::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('shift-time-preset.update', $preset), [
            'name' => 'New',
            'break_time' => 45,
            'id' => $preset->id,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response->assertOk();
        $this->assertSame('New', $preset->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $preset = ShiftTimePreset::factory()->create();

        $response = $this->delete(route('shift-time-preset.destroy', $preset));

        $response->assertOk();
    }
}
