<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * DP-06: Fehlt die Pause beim Anlegen, wird die gesetzliche Mindestpause (ArbZG)
 * gesetzt; explizit übergebene Werte (auch 0) werden nie überschrieben.
 */
final class LegalBreakFallbackTest extends FeatureTestCase
{
    private function createPreset(array $overrides = []): SingleShiftPreset
    {
        return SingleShiftPreset::query()->create(array_merge([
            'name' => 'Spätschicht',
            'start_time' => '14:00:00',
            'end_time' => '23:30:00',
            'break_duration' => 0,
            'craft_id' => Craft::factory()->create()->id,
            'description' => null,
        ], $overrides));
    }

    #[Test]
    public function shift_from_preset_without_break_gets_legal_minimum(): void
    {
        $this->actingAsAdmin();
        ShiftQualification::factory()->create();
        $room = Room::factory()->create();
        $craft = Craft::factory()->create();

        // Vorlage ohne Pausenangabe anlegen (Pausenfeld leer) → 9:01 h → 45 min.
        $this->post(route('single-shift-presets.store'), [
            'name' => 'Lange Schicht',
            'start_time' => '08:00',
            'end_time' => '17:01',
            'break_duration' => null,
            'craft_id' => $craft->id,
        ]);

        $preset = SingleShiftPreset::query()->where('name', 'Lange Schicht')->firstOrFail();
        $this->assertSame(45, (int) $preset->break_duration);

        $this->post(route('shifts.createFromPresets'), [
            'room_id' => $room->id,
            'day' => '2026-05-06',
            'preset_ids' => [$preset->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'room_id' => $room->id,
            'start_date' => '2026-05-06',
            'break_minutes' => 45,
        ]);
    }

    #[Test]
    public function shift_from_preset_with_explicit_zero_break_keeps_zero(): void
    {
        $this->actingAsAdmin();
        ShiftQualification::factory()->create();
        $room = Room::factory()->create();
        $preset = $this->createPreset(['start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_duration' => 0]);

        $this->post(route('shifts.createFromPresets'), [
            'room_id' => $room->id,
            'day' => '2026-05-07',
            'preset_ids' => [$preset->id],
        ])->assertRedirect();

        $shift = Shift::query()->where('room_id', $room->id)->where('start_date', '2026-05-07')->firstOrFail();
        $this->assertSame(0, (int) $shift->break_minutes);
    }

    #[Test]
    public function multi_add_without_break_gets_legal_minimum_and_explicit_zero_stays(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $room = Room::factory()->create();

        $this->postJson(route('event.shift.store.multi.add'), [
            'craft_id' => $craft->id,
            'start' => '08:00',
            'end' => '17:01',
            'break_minutes' => null,
            'roomsAndDatesForMultiEdit' => [['roomId' => $room->id, 'day' => '2026-05-06']],
            'shiftsQualifications' => [],
        ])->assertSuccessful();

        $this->assertDatabaseHas('shifts', [
            'room_id' => $room->id,
            'start_date' => '2026-05-06',
            'break_minutes' => 45,
        ]);

        $this->postJson(route('event.shift.store.multi.add'), [
            'craft_id' => $craft->id,
            'start' => '08:00',
            'end' => '17:01',
            'break_minutes' => 0,
            'roomsAndDatesForMultiEdit' => [['roomId' => $room->id, 'day' => '2026-05-07']],
            'shiftsQualifications' => [],
        ])->assertSuccessful();

        $this->assertDatabaseHas('shifts', [
            'room_id' => $room->id,
            'start_date' => '2026-05-07',
            'break_minutes' => 0,
        ]);
    }

    #[Test]
    public function timeline_time_update_without_break_gets_legal_minimum(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '08:00:00',
            'end' => '12:00:00',
            'break_minutes' => 0,
        ]);

        $this->patchJson(route('event.shift.update.updateTime', $shift), [
            'start' => '08:00',
            'end' => '15:00',
            'break_minutes' => null,
        ])->assertSuccessful();

        $this->assertSame(30, (int) $shift->fresh()->break_minutes);

        $this->patchJson(route('event.shift.update.updateTime', $shift), [
            'start' => '08:00',
            'end' => '15:00',
            'break_minutes' => 0,
        ])->assertSuccessful();

        $this->assertSame(0, (int) $shift->fresh()->break_minutes);
    }

    #[Test]
    public function individual_time_without_break_gets_legal_minimum(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [
                [
                    'title' => 'Probe',
                    'start_time' => '09:00',
                    'end_time' => '16:00',
                    'start_date' => '2026-05-06',
                ],
            ],
        ])->assertOk();

        $time = IndividualTime::query()->where('timeable_id', $user->id)->where('title', 'Probe')->firstOrFail();
        $this->assertSame(30, (int) $time->break_minutes);
        // 7h brutto − 30 min Pause
        $this->assertSame(390, (int) $time->working_time_minutes);
    }

    #[Test]
    public function individual_time_with_explicit_zero_break_keeps_zero(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [
                [
                    'title' => 'Ohne Pause',
                    'start_time' => '09:00',
                    'end_time' => '16:00',
                    'start_date' => '2026-05-06',
                    'break_minutes' => 0,
                ],
            ],
        ])->assertOk();

        $time = IndividualTime::query()->where('timeable_id', $user->id)->where('title', 'Ohne Pause')->firstOrFail();
        $this->assertSame(0, (int) $time->break_minutes);
        $this->assertSame(420, (int) $time->working_time_minutes);
    }
}
