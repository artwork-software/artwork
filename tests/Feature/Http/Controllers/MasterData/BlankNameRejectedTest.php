<?php

namespace Tests\Feature\Http\Controllers\MasterData;

use Artwork\Modules\Area\Models\Area;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Anlegen ohne Namen (auch nur Leerzeichen) wird als Validierungsfehler abgelehnt. Vorher endete es
 * an diesen Stellen in einem Datenbankfehler (500).
 */
final class BlankNameRejectedTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function storeUris(): array
    {
        return [
            'areas' => ['/areas'],
            'saved shift plan filters' => ['/shifts/filters'],
            'collecting societies' => ['/collecting_societies'],
            'shift presets' => ['/empty/preset/store'],
        ];
    }

    #[Test]
    #[DataProvider('storeUris')]
    public function a_blank_name_is_rejected(string $uri): void
    {
        $this->actingAsAdmin();

        $this->postJson($uri, ['name' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function an_area_cannot_be_renamed_to_a_blank_name_but_keeps_its_name_on_colour_change(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create(['name' => 'Bestand']);

        $this->patchJson(route('areas.update', $area), ['name' => ''])->assertUnprocessable();
        $this->patch(route('areas.update', $area), ['color' => '#123456'])->assertValid();

        $this->assertSame('Bestand', $area->fresh()->name);
        $this->assertSame('#123456', $area->fresh()->color);
    }
}
