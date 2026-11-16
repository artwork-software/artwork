<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Shift\Models\PresetTimelineTime;
use Artwork\Modules\Shift\Models\ShiftFilter;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion der schreibenden Routen für Schichtvorlagen, Zeitleisten-Vorlagen und Schichtfilter
 * (Rechte deckt die Rollen-Matrix ab).
 */
final class ShiftPresetWriteRoutesTest extends FeatureTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->actingAsAdmin();
    }

    #[Test]
    public function preset_shift_update_changes_times_craft_and_qualification_demand(): void
    {
        $presetShift = PresetShift::factory()->create();
        $newCraft = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        PresetShiftShiftsQualifications::query()->create([
            'preset_shift_id' => $presetShift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 1,
        ]);
        $newQualification = ShiftQualification::factory()->create();

        $this->patch(route('shift.preset.update', $presetShift), [
            'start' => '09:30',
            'end' => '17:15',
            'break_minutes' => 45,
            'craft_id' => $newCraft->id,
            'description' => 'Aufbau',
            'presetShiftsQualifications' => [
                ['shift_qualification_id' => $qualification->id, 'value' => 3],
                ['shift_qualification_id' => $newQualification->id, 'value' => 2],
            ],
        ])->assertSuccessful();

        $fresh = $presetShift->fresh();
        $this->assertSame('09:30', $fresh->start);
        $this->assertSame('17:15', $fresh->end);
        $this->assertSame(45, (int) $fresh->break_minutes);
        $this->assertSame($newCraft->id, (int) $fresh->craft_id);
        $this->assertSame('Aufbau', $fresh->description);
        $this->assertSame(
            [$qualification->id => 3, $newQualification->id => 2],
            $fresh->shiftsQualifications()->pluck('value', 'shift_qualification_id')->map(fn ($v) => (int) $v)->all()
        );
    }

    #[Test]
    public function preset_shift_update_without_qualification_list_keeps_demand_and_does_not_fail(): void
    {
        $presetShift = PresetShift::factory()->create();

        $this->patch(route('shift.preset.update', $presetShift), ['description' => 'nur Notiz'])
            ->assertSuccessful();

        $this->assertSame('nur Notiz', $presetShift->fresh()->description);
    }

    #[Test]
    public function preset_shift_update_rejects_invalid_values_instead_of_server_error(): void
    {
        $presetShift = PresetShift::factory()->create();
        $originalStart = $presetShift->fresh()->start;

        $this->patchJson(route('shift.preset.update', $presetShift), [
            'start' => 'morgens',
            'break_minutes' => -5,
            'craft_id' => 999999,
            'presetShiftsQualifications' => [
                ['shift_qualification_id' => 999999, 'value' => -1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'start',
            'break_minutes',
            'craft_id',
            'presetShiftsQualifications.0.shift_qualification_id',
            'presetShiftsQualifications.0.value',
        ]);

        $this->assertSame($originalStart, $presetShift->fresh()->start);
    }

    #[Test]
    public function shift_preset_update_renames_preset(): void
    {
        $shiftPreset = ShiftPreset::factory()->create(['name' => 'Alt']);

        $this->patch(route('update.shift.preset', $shiftPreset), ['name' => 'Neu'])->assertSuccessful();

        $this->assertSame('Neu', $shiftPreset->fresh()->name);
    }

    #[Test]
    public function shift_preset_update_rejects_empty_or_overlong_name_instead_of_server_error(): void
    {
        $shiftPreset = ShiftPreset::factory()->create(['name' => 'Alt']);

        $this->patchJson(route('update.shift.preset', $shiftPreset), ['name' => null])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->patchJson(route('update.shift.preset', $shiftPreset), ['name' => str_repeat('x', 256)])
            ->assertStatus(422)->assertJsonValidationErrors('name');

        $this->assertSame('Alt', $shiftPreset->fresh()->name);
    }

    #[Test]
    public function timeline_preset_update_replaces_name_and_times(): void
    {
        $timelinePreset = ShiftPresetTimeline::factory()->create(['name' => 'Probe']);
        $oldTime = PresetTimelineTime::factory()->create([
            'preset_timeline_id' => $timelinePreset->id,
            'start' => '10:00',
            'end' => '11:00',
            'description' => 'alt',
        ]);

        $this->patch(route('timeline-presets.update', $timelinePreset), [
            'name' => 'Hauptprobe',
            'dataset' => [
                ['start' => '18:00', 'end' => '19:30', 'description' => 'Einlass'],
                ['start' => '9:00', 'end' => null, 'description' => ''],
            ],
        ])->assertSuccessful();

        $this->assertSame('Hauptprobe', $timelinePreset->fresh()->name);
        $this->assertNull($oldTime->fresh());
        $times = $timelinePreset->times()->orderBy('id')->get();
        $this->assertCount(2, $times);
        $this->assertSame('Einlass', $times[0]->description);
        $this->assertNull($times[1]->end);
    }

    #[Test]
    public function timeline_preset_update_validates_rows_instead_of_server_error(): void
    {
        $timelinePreset = ShiftPresetTimeline::factory()->create(['name' => 'Probe']);
        PresetTimelineTime::factory()->create([
            'preset_timeline_id' => $timelinePreset->id,
            'start' => '10:00',
            'end' => '11:00',
        ]);

        $this->patchJson(route('timeline-presets.update', $timelinePreset), [
            'name' => 'Probe',
            'dataset' => [
                ['start' => 'abends', 'end' => '19:00', 'description' => str_repeat('x', 256)],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['dataset.0.start', 'dataset.0.description']);

        // Bestehende Zeiten dürfen bei ungültiger Eingabe nicht verloren gehen
        $this->assertSame(1, $timelinePreset->times()->count());
    }

    #[Test]
    public function timeline_preset_update_accepts_rows_without_description_key(): void
    {
        $timelinePreset = ShiftPresetTimeline::factory()->create(['name' => 'Probe']);

        $this->patch(route('timeline-presets.update', $timelinePreset), [
            'name' => 'Probe',
            'dataset' => [['start' => '10:00', 'end' => '11:00']],
        ])->assertSuccessful();

        $this->assertNull($timelinePreset->times()->first()->description);
    }

    #[Test]
    public function timeline_preset_duplicate_copies_name_and_times(): void
    {
        $timelinePreset = ShiftPresetTimeline::factory()->create(['name' => 'Vorstellung']);
        PresetTimelineTime::factory()->create([
            'preset_timeline_id' => $timelinePreset->id,
            'start' => '19:00',
            'end' => '21:00',
            'description' => 'Show',
        ]);

        $this->post(route('timeline-preset.duplicate', $timelinePreset))->assertSuccessful();

        $copy = ShiftPresetTimeline::query()->where('name', 'Vorstellung (copy)')->first();
        $this->assertNotNull($copy);
        $this->assertNotSame($timelinePreset->id, $copy->id);
        $this->assertSame(['Show'], $copy->times()->pluck('description')->all());
        $this->assertSame(1, $timelinePreset->times()->count());
    }

    #[Test]
    public function preset_timeline_delete_route_removes_timeline_preset_with_times(): void
    {
        $timelinePreset = ShiftPresetTimeline::factory()->create();
        $time = PresetTimelineTime::factory()->create(['preset_timeline_id' => $timelinePreset->id]);

        $this->delete(route('preset.delete.timeline.row', $timelinePreset))->assertSuccessful();

        $this->assertNull($timelinePreset->fresh());
        $this->assertNull($time->fresh());
    }

    #[Test]
    public function shift_filter_destroy_deletes_own_filter_only(): void
    {
        $ownFilter = ShiftFilter::factory()->create(['user_id' => $this->admin->id]);
        $foreignFilter = ShiftFilter::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->delete('/shifts/filters/' . $ownFilter->id)->assertSuccessful();
        $this->delete('/shifts/filters/' . $foreignFilter->id)->assertForbidden();
        $this->delete('/shifts/filters/999999')->assertNotFound();

        $this->assertNull($ownFilter->fresh());
        $this->assertNotNull($foreignFilter->fresh());
    }
}
