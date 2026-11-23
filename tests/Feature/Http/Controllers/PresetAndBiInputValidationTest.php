<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\ShiftPreset;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/** Eingaben, die ungeprüft zu 500ern führten: Schichtvorlage anlegen, Zeitleisten-Vorlage anlegen, BI-Projektwerte. */
final class PresetAndBiInputValidationTest extends FeatureTestCase
{
    #[Test]
    public function preset_shift_store_validates_instead_of_failing(): void
    {
        $this->actingAsAdmin();
        $shiftPreset = ShiftPreset::factory()->create();

        $this->post(route('shift.preset.store', $shiftPreset->id), ['start' => '25:99'])
            ->assertSessionHasErrors(['start', 'end', 'break_minutes', 'craft_id']);
        $this->post(route('shift.preset.store', 999999999), [])->assertNotFound();

        $craft = Craft::factory()->create();
        $this->post(route('shift.preset.store', $shiftPreset->id), [
            'start' => '10:00',
            'end' => '18:00',
            'break_minutes' => 30,
            'craft_id' => $craft->id,
        ])->assertSessionHasNoErrors();

        self::assertSame(1, PresetShift::where('shift_preset_id', $shiftPreset->id)->count());
    }

    #[Test]
    public function timeline_preset_store_rejects_invalid_rows(): void
    {
        $this->actingAsAdmin();

        $this->post(route('timeline-presets.store'), [
            'name' => 'Ablauf',
            'dataset' => [['start' => 'kaputt', 'end' => '12:00', 'description' => str_repeat('x', 300)]],
        ])->assertSessionHasErrors(['dataset.0.start', 'dataset.0.description']);
    }

    #[Test]
    public function bi_project_totals_are_capped_to_the_column_range(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->putJson(route('projects.bi.update-data', $project->id), [
            'visitors_total' => 5000000000,
            'revenue_total' => 100000000000,
            'scope' => 'actual',
        ])->assertUnprocessable()->assertJsonValidationErrors(['visitors_total', 'revenue_total']);
    }
}
