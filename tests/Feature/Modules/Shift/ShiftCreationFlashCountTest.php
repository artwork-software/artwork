<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 2b: Nach Mehrfachanlage und Anlage aus Vorlagen nennt die Flash-Meldung die Anzahl
 * („12 Schichten angelegt", „3 Vorlagen übersprungen: …") — der globale Toast im AppLayout zeigt sie.
 */
final class ShiftCreationFlashCountTest extends FeatureTestCase
{
    private function createPreset(array $overrides = []): SingleShiftPreset
    {
        return SingleShiftPreset::query()->create(array_merge([
            'name' => 'Spätschicht',
            'start_time' => '14:00:00',
            'end_time' => '23:30:00',
            'break_duration' => 30,
            'craft_id' => Craft::factory()->create()->id,
            'description' => null,
        ], $overrides));
    }

    #[Test]
    public function multi_add_flashes_created_count(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $roomA = Room::factory()->create();
        $roomB = Room::factory()->create();

        $this->postJson(route('event.shift.store.multi.add'), [
            'craft_id' => $craft->id,
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'roomsAndDatesForMultiEdit' => [
                ['roomId' => $roomA->id, 'day' => '2026-05-06'],
                ['roomId' => $roomB->id, 'day' => '2026-05-06'],
                ['roomId' => $roomA->id, 'day' => '2026-05-07'],
            ],
            'shiftsQualifications' => [],
        ])
            ->assertSuccessful()
            ->assertSessionHas('success', __(':count shifts created.', ['count' => 3]));
    }

    #[Test]
    public function multi_add_with_single_shift_uses_singular(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $room = Room::factory()->create();

        $this->postJson(route('event.shift.store.multi.add'), [
            'craft_id' => $craft->id,
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'roomsAndDatesForMultiEdit' => [
                ['roomId' => $room->id, 'day' => '2026-05-06'],
            ],
            'shiftsQualifications' => [],
        ])
            ->assertSuccessful()
            ->assertSessionHas('success', __('1 shift created.'));
    }

    #[Test]
    public function create_from_presets_flashes_created_and_skipped_counts(): void
    {
        $this->actingAsAdmin();
        ShiftQualification::factory()->create();
        $room = Room::factory()->create();

        $presetA = $this->createPreset(['name' => 'Frühschicht', 'start_time' => '06:00:00', 'end_time' => '14:00:00']);
        $presetB = $this->createPreset(['name' => 'Spätschicht']);
        // Ohne Gewerk kann keine Schicht entstehen → wird übersprungen und namentlich genannt
        $presetWithoutCraft = $this->createPreset(['name' => 'Ohne Gewerk', 'craft_id' => null]);

        $response = $this->post(route('shifts.createFromPresets'), [
            'room_id' => $room->id,
            'day' => '2026-05-06',
            'preset_ids' => [$presetA->id, $presetB->id, $presetWithoutCraft->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', function (string $message): bool {
            return str_contains($message, __(':count shifts created.', ['count' => 2]))
                && str_contains($message, __('1 template skipped: :names', ['names' => 'Ohne Gewerk']));
        });

        $this->assertDatabaseCount('shifts', 2);
    }

    #[Test]
    public function create_from_presets_without_skips_has_no_skip_note(): void
    {
        $this->actingAsAdmin();
        ShiftQualification::factory()->create();
        $room = Room::factory()->create();
        $preset = $this->createPreset(['name' => 'Tagschicht', 'start_time' => '09:00:00', 'end_time' => '17:00:00']);

        $this->post(route('shifts.createFromPresets'), [
            'room_id' => $room->id,
            'day' => '2026-05-07',
            'preset_ids' => [$preset->id],
        ])
            ->assertRedirect()
            ->assertSessionHas('success', __('1 shift created.'));
    }
}
