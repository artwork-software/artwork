<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabArtistResidenciesService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Unterkünfte werden im CRM gepflegt, Künstler*innenaufenthalte und Zimmertypen hängen am
 * Unterkunfts-Datensatz. Beide Seiten entstehen deshalb immer zusammen: beim Anlegen im CRM (Dialog,
 * Import, Typwechsel), beim Anlegen auf /accommodation und einmalig per Migration für den Bestand.
 */
final class CrmAccommodationLinkTest extends FeatureTestCase
{
    private const MIGRATION = 'migrations/2026_11_11_100000_link_crm_accommodation_contacts_and_accommodations.php';

    private function accommodationType(): CrmContactType
    {
        return CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'accommodation'],
            ['name' => 'Unterkunft', 'is_system' => true, 'is_active' => true]
        );
    }

    private function property(CrmContactType $type, string $name): CrmProperty
    {
        $property = CrmProperty::query()->where('name', $name)->first()
            ?? CrmProperty::query()->create([
                'crm_property_group_id' => CrmPropertyGroup::factory()->create()->id,
                'name' => $name,
                'type' => 'text',
                'sort_order' => 0,
            ]);
        $type->properties()->syncWithoutDetaching([$property->id => ['sort_order' => 0]]);

        return $property;
    }

    private function accommodationOf(CrmContact $contact): ?Accommodation
    {
        return Accommodation::query()->where('crm_contact_id', $contact->id)->first();
    }

    #[Test]
    public function a_new_accommodation_contact_gets_its_accommodation_and_is_selectable_for_residencies(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Hotel am Park',
            'property_values' => [$email->id => 'empfang@hotel-am-park.test'],
        ])->assertRedirect();

        $contact = CrmContact::query()->where('display_name', 'Hotel am Park')->sole();
        $accommodation = $this->accommodationOf($contact);

        $this->assertNotNull($accommodation);
        $this->assertSame('Hotel am Park', $accommodation->name);
        $this->assertSame('empfang@hotel-am-park.test', $accommodation->email);
        $this->assertSame(Accommodation::class, $contact->entity_type);
        $this->assertSame($accommodation->id, (int) $contact->entity_id);

        $payload = app(ProjectTabArtistResidenciesService::class)
            ->buildArtistResidenciesPayload(Project::factory()->create());
        $this->assertContains($accommodation->id, $payload['accommodations']->pluck('id')->all());
    }

    #[Test]
    public function room_types_of_a_new_accommodation_contact_can_be_saved(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Pension Zimmertyp',
        ])->assertRedirect();
        $contact = CrmContact::query()->where('display_name', 'Pension Zimmertyp')->sole();

        $this->post(route('crm.contacts.room-types.store', $contact), [
            'name' => 'Einzelzimmer ' . uniqid(),
            'cost_per_night' => 85,
        ])->assertRedirect();

        $this->assertCount(1, $contact->roomTypes()->get());
        $this->assertCount(1, $this->accommodationOf($contact)->roomTypes()->get());
    }

    #[Test]
    public function renaming_the_contact_renames_the_accommodation_and_sticks(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Alter Name',
        ])->assertRedirect();
        $contact = CrmContact::query()->where('display_name', 'Alter Name')->sole();

        $this->patch(route('crm.contacts.update', $contact), ['display_name' => 'Neuer Name'])
            ->assertRedirect();

        $this->assertSame('Neuer Name', $contact->fresh()->display_name);
        $this->assertSame('Neuer Name', $this->accommodationOf($contact)->name);
    }

    #[Test]
    public function contacts_of_other_types_get_no_accommodation(): void
    {
        $this->actingAsAdmin();
        $this->accommodationType();
        $otherType = CrmContactType::factory()->create();
        $accommodationsBefore = Accommodation::count();

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $otherType->id,
            'display_name' => 'Presse Kontakt',
        ])->assertRedirect();

        $this->assertSame($accommodationsBefore, Accommodation::count());
        $this->assertNull(CrmContact::query()->where('display_name', 'Presse Kontakt')->sole()->entity_id);
    }

    #[Test]
    public function imported_accommodation_contacts_get_their_accommodation(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');
        $csv = "Name,Mail\nImport Hotel,import@hotel.test\n";

        $this->post(route('crm.import.upload'), [
            'file' => UploadedFile::fake()->createWithContent('unterkuenfte.csv', $csv),
            'use_type_column' => '0',
            'crm_contact_type_id' => $type->id,
        ])->assertOk();
        $this->post(route('crm.import.execute'), [
            'mapping' => ['display_name' => 0, 'properties' => [(string) $email->id => 1]],
            'duplicates' => null,
        ])->assertRedirect();

        $this->assertSame([], session('importResult')['skipped']);
        $contact = CrmContact::query()->where('display_name', 'Import Hotel')->sole();
        $this->assertSame('Import Hotel', $this->accommodationOf($contact)?->name);
        $this->assertSame('import@hotel.test', $this->accommodationOf($contact)->email);
    }

    #[Test]
    public function changing_a_free_contact_into_an_accommodation_creates_the_accommodation(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();
        $contact = CrmContact::factory()->create(['display_name' => 'Wird Unterkunft']);

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $type->id])
            ->assertRedirect();

        $this->assertSame('Wird Unterkunft', $this->accommodationOf($contact)?->name);
        $this->assertSame(Accommodation::class, $contact->fresh()->entity_type);
    }

    #[Test]
    public function changing_the_type_carries_existing_values_into_the_accommodation(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');
        $phone = $this->property($type, 'Telefon');
        $contact = CrmContact::factory()->create(['display_name' => 'Wird Unterkunft mit Werten']);
        $contact->contactType->properties()->syncWithoutDetaching([
            $email->id => ['sort_order' => 0],
            $phone->id => ['sort_order' => 1],
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $email->id,
            'value' => 'alt@unterkunft.test',
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $phone->id,
            'value' => '030 123456',
        ]);

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $type->id])
            ->assertRedirect();

        $accommodation = $this->accommodationOf($contact);
        $this->assertSame('alt@unterkunft.test', $accommodation?->email);
        $this->assertSame('030 123456', $accommodation->phone_number);

        $accommodation->syncToCrm();

        $this->assertSame('alt@unterkunft.test', CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $email->id)
            ->value('value'));
        $this->assertSame('030 123456', CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $phone->id)
            ->value('value'));
    }

    #[Test]
    public function an_accommodation_created_on_the_accommodation_page_gets_its_crm_contact(): void
    {
        $this->actingAsAdmin();
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');
        $roomType = AccommodationRoomType::query()->create(['name' => 'Doppelzimmer ' . uniqid()]);

        $this->post(route('accommodation.store'), [
            'name' => 'Gasthof Seite',
            'email' => 'info@gasthof-seite.test',
            'room_types' => [$roomType->id],
            'room_type_costs' => [$roomType->id => 70],
        ])->assertRedirect();

        $accommodation = Accommodation::query()->where('name', 'Gasthof Seite')->sole();
        $contact = CrmContact::query()->find($accommodation->crm_contact_id);

        $this->assertNotNull($contact);
        $this->assertSame($type->id, $contact->crm_contact_type_id);
        $this->assertSame('Gasthof Seite', $contact->display_name);
        $this->assertSame($accommodation->id, (int) $contact->entity_id);
        $this->assertSame('info@gasthof-seite.test', CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $email->id)
            ->value('value'));
        $this->assertSame([$roomType->id], $contact->roomTypes()->pluck('accommodation_room_types.id')->all());

        $this->patch(route('accommodation.update', $accommodation), [
            'id' => $accommodation->id,
            'name' => 'Gasthof Seite neu',
            'room_types' => [$roomType->id],
        ])->assertRedirect();

        $this->assertSame('Gasthof Seite neu', $contact->fresh()->display_name);
    }

    #[Test]
    public function migration_links_existing_contacts_and_accommodations_in_both_directions(): void
    {
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');

        $contactOnly = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Nur im CRM',
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contactOnly->id,
            'crm_property_id' => $email->id,
            'value' => 'crm@unterkunft.test',
        ]);

        $accommodationOnly = Accommodation::factory()->create([
            'name' => 'Nur als Unterkunft',
            'email' => 'seite@unterkunft.test',
        ]);
        $roomType = AccommodationRoomType::query()->create(['name' => 'Suite ' . uniqid()]);
        $accommodationOnly->roomTypes()->attach($roomType->id, ['cost_per_night' => 120]);
        $residency = ArtistResidency::factory()->create([
            'accommodation_id' => $accommodationOnly->id,
            'accommodation_crm_contact_id' => null,
        ]);

        $pairedContact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Doppelt gepflegt',
        ]);
        $pairedAccommodation = Accommodation::factory()->create(['name' => 'Doppelt gepflegt']);

        $trashedContact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Im Papierkorb',
        ]);
        $trashedContact->delete();

        $otherTypeContact = CrmContact::factory()->create(['display_name' => 'Andere Art']);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $created = $this->accommodationOf($contactOnly);
        $this->assertSame('Nur im CRM', $created?->name);
        $this->assertSame('crm@unterkunft.test', $created->email);
        $this->assertSame($created->id, (int) $contactOnly->fresh()->entity_id);
        $this->assertSame(1, Accommodation::query()->where('name', 'Nur im CRM')->count());

        $newContact = CrmContact::query()->find($accommodationOnly->fresh()->crm_contact_id);
        $this->assertSame('Nur als Unterkunft', $newContact?->display_name);
        $this->assertSame($type->id, $newContact->crm_contact_type_id);
        $this->assertSame($accommodationOnly->id, (int) $newContact->entity_id);
        $this->assertSame('seite@unterkunft.test', CrmPropertyValue::query()
            ->where('crm_contact_id', $newContact->id)
            ->where('crm_property_id', $email->id)
            ->value('value'));
        $this->assertSame(
            $newContact->id,
            (int) DB::table('accommodation_accommodation_room_type')
                ->where('accommodation_id', $accommodationOnly->id)
                ->value('crm_contact_id')
        );
        $this->assertSame($newContact->id, (int) $residency->fresh()->accommodation_crm_contact_id);
        $this->assertSame(1, CrmContact::query()->where('display_name', 'Nur als Unterkunft')->count());

        $this->assertSame($pairedContact->id, (int) $pairedAccommodation->fresh()->crm_contact_id);
        $this->assertSame($pairedAccommodation->id, (int) $pairedContact->fresh()->entity_id);
        $this->assertSame(1, Accommodation::query()->where('name', 'Doppelt gepflegt')->count());
        $this->assertSame(1, CrmContact::query()->where('display_name', 'Doppelt gepflegt')->count());

        $this->assertNull(CrmContact::withTrashed()->find($trashedContact->id)->entity_id);
        $this->assertSame(0, Accommodation::query()->where('name', 'Im Papierkorb')->count());
        $this->assertNull($otherTypeContact->fresh()->entity_id);
    }

    #[Test]
    public function migration_fills_empty_fields_on_both_sides_of_a_pair_linked_by_name(): void
    {
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');
        $phone = $this->property($type, 'Telefon');
        $city = $this->property($type, 'Stadt');

        $contact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => ' Paar mit Werten ',
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $email->id,
            'value' => 'crm@paar.test',
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $city->id,
            'value' => 'Köln',
        ]);
        $accommodation = Accommodation::factory()->create([
            'name' => 'Paar mit Werten',
            'email' => null,
            'phone_number' => '0221 99',
            'location' => 'Bonn',
        ]);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $accommodation->refresh();
        $this->assertSame($contact->id, (int) $accommodation->crm_contact_id);
        $this->assertSame('crm@paar.test', $accommodation->email);
        $this->assertSame('0221 99', $accommodation->phone_number);
        $this->assertSame('Bonn', $accommodation->location);

        $values = CrmPropertyValue::query()->where('crm_contact_id', $contact->id)->pluck('value', 'crm_property_id');
        $this->assertSame('crm@paar.test', $values[$email->id]);
        $this->assertSame('0221 99', $values[$phone->id]);
        $this->assertSame('Köln', $values[$city->id]);
        $this->assertCount(3, $values);
    }

    #[Test]
    public function migration_links_by_name_only_when_the_name_is_exactly_equal_and_unambiguous(): void
    {
        $type = $this->accommodationType();

        $accentContact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Hotel Bär',
        ]);
        $accentAccommodation = Accommodation::factory()->create(['name' => 'Hotel Bar']);

        $caseContact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'pension linde',
        ]);
        $caseAccommodation = Accommodation::factory()->create(['name' => 'Pension Linde']);

        $twinContact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Zwilling',
        ]);
        $firstTwin = Accommodation::factory()->create(['name' => 'Zwilling']);
        $secondTwin = Accommodation::factory()->create(['name' => 'Zwilling']);

        $firstNamesake = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Namensvetter',
        ]);
        $secondNamesake = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Namensvetter',
        ]);
        $namesakeAccommodation = Accommodation::factory()->create(['name' => 'Namensvetter']);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertSame('Hotel Bär', $this->accommodationOf($accentContact)?->name);
        $this->assertNotSame($accentContact->id, (int) $accentAccommodation->fresh()->crm_contact_id);
        $this->assertSame('pension linde', $this->accommodationOf($caseContact)?->name);
        $this->assertNotSame($caseContact->id, (int) $caseAccommodation->fresh()->crm_contact_id);

        $ownTwin = $this->accommodationOf($twinContact);
        $this->assertNotNull($ownTwin);
        $this->assertNotContains($ownTwin->id, [$firstTwin->id, $secondTwin->id]);
        $this->assertSame(3, Accommodation::query()->where('name', 'Zwilling')->count());

        $namesakeAccommodationIds = [
            $this->accommodationOf($firstNamesake)?->id,
            $this->accommodationOf($secondNamesake)?->id,
        ];
        $this->assertNotContains(null, $namesakeAccommodationIds);
        $this->assertNotContains($namesakeAccommodation->id, $namesakeAccommodationIds);

        foreach ([$accentAccommodation, $caseAccommodation, $firstTwin, $secondTwin, $namesakeAccommodation] as $left) {
            $ownContact = CrmContact::query()->find($left->fresh()->crm_contact_id);
            $this->assertSame($left->id, (int) $ownContact?->entity_id);
        }
    }

    #[Test]
    public function migration_gives_a_contact_pointing_to_a_deleted_accommodation_a_new_one(): void
    {
        $type = $this->accommodationType();
        $email = $this->property($type, 'Email');
        $deleted = Accommodation::factory()->create(['name' => 'Gelöschte Unterkunft']);
        $contact = CrmContact::factory()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Verwaister Kontakt',
            'entity_type' => $deleted->getMorphClass(),
            'entity_id' => $deleted->id,
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $email->id,
            'value' => 'verwaist@unterkunft.test',
        ]);
        DB::table('accommodations')->where('id', $deleted->id)->delete();

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $accommodation = $this->accommodationOf($contact);
        $this->assertSame('Verwaister Kontakt', $accommodation?->name);
        $this->assertSame('verwaist@unterkunft.test', $accommodation->email);
        $this->assertSame($accommodation->id, (int) $contact->fresh()->entity_id);
        $this->assertSame(1, Accommodation::query()->where('name', 'Verwaister Kontakt')->count());
    }
}
