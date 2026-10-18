<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetShift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Notiz an Vorlagen-Schichten (ShiftNoteComponent mit is-preset): gleiche Grenzen wie
 * Schichtnotizen (TEXT, max. 10.000 Zeichen), Leeren ist erlaubt.
 */
final class PresetShiftNoteUpdateTest extends FeatureTestCase
{
    #[Test]
    public function a_preset_shift_note_longer_than_250_characters_is_saved(): void
    {
        $this->actingAsAdmin();
        $presetShift = PresetShift::factory()->create(['description' => 'Alt']);
        // ohne Leerzeichen am Ende – TrimStrings kürzt es beim Speichern weg
        $description = "Bühnenanweisung\n" . rtrim(str_repeat('Podest, Aushang, Schüler*innen. ', 40));

        $this->patchJson(route('preset.shift.update.updateDescription', $presetShift), [
            'description' => $description,
        ])->assertOk();

        $this->assertSame($description, $presetShift->fresh()->description);
    }

    #[Test]
    public function a_preset_shift_note_can_be_cleared(): void
    {
        $this->actingAsAdmin();
        $presetShift = PresetShift::factory()->create(['description' => 'Alt']);

        $this->patchJson(route('preset.shift.update.updateDescription', $presetShift), [
            'description' => null,
        ])->assertOk();

        $this->assertNull($presetShift->fresh()->description);
    }

    #[Test]
    public function an_overlong_preset_shift_note_is_rejected(): void
    {
        $this->actingAsAdmin();
        $presetShift = PresetShift::factory()->create(['description' => 'Alt']);

        $this->patchJson(route('preset.shift.update.updateDescription', $presetShift), [
            'description' => str_repeat('a', 10001),
        ])->assertUnprocessable()->assertJsonValidationErrors('description');

        $this->assertSame('Alt', $presetShift->fresh()->description);
    }

    #[Test]
    public function a_note_must_be_text(): void
    {
        $this->actingAsAdmin();
        $presetShift = PresetShift::factory()->create(['description' => 'Alt']);

        $this->patchJson(route('preset.shift.update.updateDescription', $presetShift), [
            'description' => ['nicht', 'erlaubt'],
        ])->assertUnprocessable()->assertJsonValidationErrors('description');
    }
}
