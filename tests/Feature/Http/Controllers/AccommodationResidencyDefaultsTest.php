<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabArtistResidenciesService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Vorgaben für Tagegeld und Abzug pro Frühstück je Unterkunft (Künstler*innenaufenthalte).
 * Leer = allgemeine Vorgabe aus den Künstler*innenaufenthalt-Einstellungen.
 */
final class AccommodationResidencyDefaultsTest extends FeatureTestCase
{
    #[Test]
    public function residency_defaults_are_stored_with_a_new_accommodation(): void
    {
        $this->actingAsAdmin();

        $this->post(route('accommodation.store'), [
            ...$this->accommodationPayload(),
            'daily_allowance' => '32.50',
            'breakfast_deduction_per_day' => '6.10',
        ])->assertRedirect();

        $accommodation = Accommodation::query()->where('name', 'Hotel Vorgabe')->sole();
        $this->assertSame(32.5, $accommodation->daily_allowance);
        $this->assertSame(6.1, $accommodation->breakfast_deduction_per_day);
    }

    #[Test]
    public function residency_defaults_can_be_changed_and_cleared(): void
    {
        $this->actingAsAdmin();
        $accommodation = Accommodation::factory()->create([
            'daily_allowance' => 20,
            'breakfast_deduction_per_day' => 5,
        ]);

        $this->patch(route('accommodation.update', $accommodation), [
            ...$this->accommodationPayload(),
            'id' => $accommodation->id,
            'daily_allowance' => '0',
            'breakfast_deduction_per_day' => '',
        ])->assertRedirect();

        $accommodation->refresh();
        $this->assertSame(0.0, $accommodation->daily_allowance);
        $this->assertNull($accommodation->breakfast_deduction_per_day);
    }

    #[Test]
    public function negative_residency_defaults_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('accommodation.store'), [
            ...$this->accommodationPayload(),
            'daily_allowance' => -1,
            'breakfast_deduction_per_day' => -2,
        ])->assertJsonValidationErrors(['daily_allowance', 'breakfast_deduction_per_day']);
    }

    #[Test]
    public function residency_defaults_reach_the_artist_residency_tab_payload(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $accommodation = Accommodation::factory()->create([
            'daily_allowance' => 28,
            'breakfast_deduction_per_day' => null,
        ]);

        $payload = app(ProjectTabArtistResidenciesService::class)
            ->buildArtistResidenciesPayload($project);

        $serialized = collect($payload['accommodations']->toArray())->firstWhere('id', $accommodation->id);
        $this->assertSame(28.0, $serialized['daily_allowance']);
        $this->assertNull($serialized['breakfast_deduction_per_day']);
    }

    /**
     * @return array<string, mixed>
     */
    private function accommodationPayload(): array
    {
        $roomType = AccommodationRoomType::query()->create(['name' => 'Einzelzimmer ' . uniqid()]);

        return [
            'name' => 'Hotel Vorgabe',
            'room_types' => [$roomType->id],
            'room_type_costs' => [$roomType->id => 90],
        ];
    }
}
