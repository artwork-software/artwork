<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * service_providers.note ist varchar(500): längere Notizen werden als Validierungsfehler
 * abgelehnt statt als Datenbankfehler (500) zu enden.
 */
final class ServiceProviderNoteLengthTest extends FeatureTestCase
{
    /**
     * @return array<string, string|null>
     */
    private function payload(?string $note): array
    {
        return [
            'provider_name' => 'Dienstleister',
            'note' => $note,
        ];
    }

    #[Test]
    public function a_note_with_500_characters_is_saved(): void
    {
        $this->actingAsAdmin();
        $serviceProvider = ServiceProvider::factory()->create();
        $note = str_repeat('ä', 500);

        $this->patchJson(route('service_provider.update', $serviceProvider), $this->payload($note))
            ->assertOk();

        $this->assertSame($note, $serviceProvider->fresh()->note);
    }

    #[Test]
    public function a_note_longer_than_500_characters_is_rejected(): void
    {
        $this->actingAsAdmin();
        $serviceProvider = ServiceProvider::factory()->create(['note' => 'Alt']);

        $this->patchJson(route('service_provider.update', $serviceProvider), $this->payload(str_repeat('a', 501)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');

        $this->assertSame('Alt', $serviceProvider->fresh()->note);
    }

    #[Test]
    public function the_note_can_be_cleared(): void
    {
        $this->actingAsAdmin();
        $serviceProvider = ServiceProvider::factory()->create(['note' => 'Alt']);

        $this->patchJson(route('service_provider.update', $serviceProvider), $this->payload(null))
            ->assertOk();

        $this->assertNull($serviceProvider->fresh()->note);
    }
}
