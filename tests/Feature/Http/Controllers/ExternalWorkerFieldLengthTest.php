<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Stammdaten von Freelancer:innen und Dienstleistern: Eingaben, die länger sind als die Spalte,
 * werden als Validierungsfehler abgelehnt statt als Datenbankfehler (500) zu enden.
 */
final class ExternalWorkerFieldLengthTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function freelancerFields(): array
    {
        return [
            'first_name' => ['first_name', 255],
            'last_name' => ['last_name', 255],
            'position' => ['position', 255],
            'business' => ['business', 255],
            'phone_number' => ['phone_number', 255],
            'street' => ['street', 255],
            'zip_code' => ['zip_code', 255],
            'location' => ['location', 255],
            'note' => ['note', 500],
        ];
    }

    #[Test]
    #[DataProvider('freelancerFields')]
    public function freelancer_fields_longer_than_the_column_are_rejected(string $field, int $max): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create(['first_name' => 'Alt', 'last_name' => 'Name']);

        $this->patchJson(route('freelancer.update', $freelancer), [
            'first_name' => 'Neu',
            'last_name' => 'Name',
            $field => str_repeat('a', $max + 1),
        ])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame('Alt', $freelancer->fresh()->first_name);
    }

    #[Test]
    public function a_freelancer_note_with_500_characters_is_saved(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();
        $note = str_repeat('ä', 500);

        $this->patchJson(route('freelancer.update', $freelancer), [
            'first_name' => 'Vor',
            'last_name' => 'Nach',
            'note' => $note,
        ])->assertOk();

        $this->assertSame($note, $freelancer->fresh()->note);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function serviceProviderFields(): array
    {
        return [
            'provider_name' => ['provider_name'],
            'phone_number' => ['phone_number'],
            'street' => ['street'],
            'zip_code' => ['zip_code'],
            'location' => ['location'],
        ];
    }

    #[Test]
    #[DataProvider('serviceProviderFields')]
    public function service_provider_fields_longer_than_the_column_are_rejected(string $field): void
    {
        $this->actingAsAdmin();
        $serviceProvider = ServiceProvider::factory()->create(['provider_name' => 'Alt']);

        $this->patchJson(route('service_provider.update', $serviceProvider), [
            'provider_name' => 'Neu',
            $field => str_repeat('a', 256),
        ])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame('Alt', $serviceProvider->fresh()->provider_name);
    }

    #[Test]
    public function deleting_a_freelancer_from_the_detail_page_leads_to_the_list(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();

        $this->from(route('freelancer.show', $freelancer) . '?tab=plan')
            ->delete(route('freelancer.destroy', $freelancer))
            ->assertRedirect(route('users'));

        $this->assertNull(Freelancer::find($freelancer->id));
    }

    #[Test]
    public function deleting_a_freelancer_from_the_list_stays_on_the_list(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();
        $other = Freelancer::factory()->create();

        $this->from(route('users'))
            ->delete(route('freelancer.destroy', $freelancer))
            ->assertRedirect(route('users'));

        // die Detailseite einer anderen Person ist kein Grund für den Sprung zur Liste
        $this->from(route('freelancer.show', $freelancer))
            ->delete(route('freelancer.destroy', $other))
            ->assertRedirect(route('freelancer.show', $freelancer));
    }

    #[Test]
    public function deleting_a_service_provider_from_the_detail_page_leads_to_the_list(): void
    {
        $this->actingAsAdmin();
        $serviceProvider = ServiceProvider::factory()->create();

        $this->from(route('service_provider.show', $serviceProvider))
            ->delete(route('service_provider.destroy', $serviceProvider))
            ->assertRedirect(route('users'));

        $this->assertNull(ServiceProvider::find($serviceProvider->id));
    }
}
