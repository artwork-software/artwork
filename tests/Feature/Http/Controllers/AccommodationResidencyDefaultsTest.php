<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabArtistResidenciesService;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
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
    public function accommodation_without_room_types_can_still_be_updated(): void
    {
        $this->actingAsAdmin();
        $accommodation = Accommodation::factory()->create();

        $this->patch(route('accommodation.update', $accommodation), [
            'id' => $accommodation->id,
            'name' => 'Hotel ohne Zimmertyp',
            'daily_allowance' => '41',
            'room_types' => [],
            'room_type_costs' => [],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $accommodation->refresh();
        $this->assertSame('Hotel ohne Zimmertyp', $accommodation->name);
        $this->assertSame(41.0, $accommodation->daily_allowance);
    }

    #[Test]
    public function last_room_type_can_be_removed(): void
    {
        $this->actingAsAdmin();
        $accommodation = Accommodation::factory()->create();
        $roomType = AccommodationRoomType::query()->create(['name' => 'Doppelzimmer ' . uniqid()]);
        $accommodation->roomTypes()->attach($roomType->id, ['cost_per_night' => 80]);

        $this->patch(route('accommodation.update', $accommodation), [
            'id' => $accommodation->id,
            'name' => $accommodation->name,
            'room_types' => [],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertCount(0, $accommodation->roomTypes()->get());
    }

    #[Test]
    public function missing_room_types_field_is_rejected_on_update(): void
    {
        $this->actingAsAdmin();
        $accommodation = Accommodation::factory()->create();
        $roomType = AccommodationRoomType::query()->create(['name' => 'Suite ' . uniqid()]);
        $accommodation->roomTypes()->attach($roomType->id, ['cost_per_night' => 120]);

        $this->patchJson(route('accommodation.update', $accommodation), [
            'id' => $accommodation->id,
            'name' => $accommodation->name,
        ])->assertJsonValidationErrors(['room_types']);

        $this->assertCount(1, $accommodation->roomTypes()->get());
    }

    #[Test]
    public function new_accommodation_still_needs_a_room_type(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('accommodation.store'), [
            'name' => 'Hotel ohne Zimmer',
            'room_types' => [],
        ])->assertJsonValidationErrors(['room_types']);
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
     * Die Unterkunftsseite hat keinen Menüeintrag: die CRM-Detailseite eines Unterkunft-Kontakts verlinkt sie.
     */
    #[Test]
    public function crm_detail_page_of_an_accommodation_contact_links_to_the_accommodation_page(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $accommodation = Accommodation::factory()->create();
        $accommodationType = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'accommodation'],
            ['name' => 'Unterkunft', 'is_system' => true, 'is_active' => true]
        );
        $contact = CrmContact::factory()->create([
            'crm_contact_type_id' => $accommodationType->id,
            'entity_type' => $accommodation->getMorphClass(),
            'entity_id' => $accommodation->id,
        ]);
        $otherContact = CrmContact::factory()->create();

        $this->get(route('crm.contacts.show', $contact))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('accommodationUrl', route('accommodation.show', $accommodation->id)));
        $this->get(route('crm.contacts.show', $otherContact))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('accommodationUrl', null));
    }

    /**
     * Der Aufenthaltsdialog verlinkt die Unterkunftsseite nur, wenn die Person sie öffnen darf.
     */
    #[Test]
    public function residency_tab_payload_tells_whether_the_accommodation_page_may_be_opened(): void
    {
        $project = Project::factory()->create();
        $service = app(ProjectTabArtistResidenciesService::class);

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->assertTrue($service->buildArtistResidenciesPayload($project)['can_view_accommodations']);

        $this->actingAs(User::factory()->create());
        $this->assertFalse($service->buildArtistResidenciesPayload($project)['can_view_accommodations']);
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
