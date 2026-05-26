<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetShift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class PresetShiftControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_destroy_preset_shift(): void
    {
        $ps = PresetShift::factory()->create();
        $this->delete(route('preset.shift.destroy', $ps))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_preset_shift(): void
    {
        $this->actingAsAdmin();
        $ps = PresetShift::factory()->create();

        $response = $this->delete(route('preset.shift.destroy', $ps));

        $response->assertOk();
        $this->assertDatabaseMissing('preset_shifts', ['id' => $ps->id]);
    }
}
