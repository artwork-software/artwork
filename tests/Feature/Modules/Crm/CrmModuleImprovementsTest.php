<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Repositories\CrmContactRepository;
use Artwork\Modules\Crm\Services\CrmDuplicateService;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funde der CRM-Durchsicht vom 19.11.2026: Dateien von Upload-Eigenschaften und Profilbildern,
 * Kontakttyp-Regeln, Checkbox-Filter und Eingabeprüfung der Kontaktliste.
 */
final class CrmModuleImprovementsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestCase fakt bereits "local".
        Storage::fake('public');
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_MANAGER->value]);
    }

    private function uploadProperty(): CrmProperty
    {
        return CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::UPLOAD->value]);
    }

    private function storeFile(CrmContact $contact, CrmProperty $property): string
    {
        $path = 'crm-property-files/' . bin2hex(random_bytes(16)) . '.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');

        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            'value' => $path,
            'file_name' => 'Vertrag.pdf',
        ]);

        return $path;
    }

    #[Test]
    public function merged_file_survives_emptying_the_trash(): void
    {
        $type = CrmContactType::factory()->create();
        $property = $this->uploadProperty();
        $primary = CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);
        $duplicate = CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);
        $path = $this->storeFile($duplicate, $property);

        app(CrmDuplicateService::class)->merge($primary, collect([$duplicate]));

        $this->assertSame($path, $primary->propertyValues()->where('crm_property_id', $property->id)->value('value'));
        $this->assertNull($duplicate->propertyValues()->where('crm_property_id', $property->id)->value('value'));

        CrmContact::onlyTrashed()->findOrFail($duplicate->id)->forceDelete();

        Storage::disk('local')->assertExists($path);
    }

    #[Test]
    public function force_delete_keeps_a_file_another_contact_still_points_to(): void
    {
        $property = $this->uploadProperty();
        $contact = CrmContact::factory()->create();
        $other = CrmContact::factory()->create();
        $path = $this->storeFile($contact, $property);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $other->id,
            'crm_property_id' => $property->id,
            'value' => $path,
        ]);

        $contact->forceDelete();

        Storage::disk('local')->assertExists($path);
    }

    #[Test]
    public function deleting_an_upload_property_removes_its_files(): void
    {
        $property = $this->uploadProperty();
        $path = $this->storeFile(CrmContact::factory()->create(), $property);

        $this->delete(route('crm.properties.destroy', $property))->assertRedirect();

        Storage::disk('local')->assertMissing($path);
    }

    #[Test]
    public function deleting_a_property_group_removes_the_files_of_its_upload_properties(): void
    {
        $group = CrmPropertyGroup::factory()->create();
        $property = CrmProperty::factory()->create([
            'crm_property_group_id' => $group->id,
            'type' => CrmPropertyTypeEnum::UPLOAD->value,
        ]);
        $path = $this->storeFile(CrmContact::factory()->create(), $property);

        $this->delete(route('crm.groups.destroy', $group))->assertRedirect();

        Storage::disk('local')->assertMissing($path);
    }

    #[Test]
    public function changing_the_contact_type_removes_files_of_dropped_properties_only(): void
    {
        $dropped = $this->uploadProperty();
        $kept = $this->uploadProperty();
        $oldType = CrmContactType::factory()->create();
        $oldType->properties()->attach([$dropped->id, $kept->id]);
        $newType = CrmContactType::factory()->create();
        $newType->properties()->attach([$kept->id]);
        $contact = CrmContact::factory()->create(['crm_contact_type_id' => $oldType->id]);
        $droppedPath = $this->storeFile($contact, $dropped);
        $keptPath = $this->storeFile($contact, $kept);

        $this->patch(route('crm.contacts.change-type', $contact), [
            'crm_contact_type_id' => $newType->id,
        ])->assertRedirect();

        Storage::disk('local')->assertMissing($droppedPath);
        Storage::disk('local')->assertExists($keptPath);
    }

    #[Test]
    public function upload_property_type_cannot_change_while_files_exist(): void
    {
        $property = $this->uploadProperty();
        $this->storeFile(CrmContact::factory()->create(), $property);

        $this->patch(route('crm.properties.update', $property), [
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ])->assertSessionHasErrors('type');

        $this->assertSame(CrmPropertyTypeEnum::UPLOAD, $property->refresh()->type);

        $empty = $this->uploadProperty();
        $this->patch(route('crm.properties.update', $empty), [
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ])->assertSessionHasNoErrors();
        $this->assertSame(CrmPropertyTypeEnum::TEXT, $empty->refresh()->type);
    }

    #[Test]
    public function file_endpoints_reject_non_upload_properties(): void
    {
        $contact = CrmContact::factory()->create();
        $textProperty = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::TEXT->value]);

        $this->post(route('crm.contacts.property-file.upload', $contact), [
            'property_id' => $textProperty->id,
            'file' => UploadedFile::fake()->create('vertrag.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('property_id');

        $this->assertDatabaseMissing('crm_property_values', ['crm_property_id' => $textProperty->id]);
    }

    #[Test]
    public function upload_values_cannot_be_set_through_property_values(): void
    {
        $contact = CrmContact::factory()->create();
        $property = $this->uploadProperty();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$property->id => 'crm-property-files/abcdef.pdf'],
        ])->assertRedirect();

        $this->assertDatabaseMissing('crm_property_values', ['crm_property_id' => $property->id]);
    }

    #[Test]
    public function replacing_and_force_deleting_removes_uploaded_profile_images(): void
    {
        $contact = CrmContact::factory()->create();

        $this->post(route('crm.contacts.profile-image', $contact), [
            'profile_image' => UploadedFile::fake()->image('eins.png'),
        ])->assertRedirect();
        $first = $contact->refresh()->getRawOriginal('profile_image');
        Storage::disk('public')->assertExists($first);

        $this->post(route('crm.contacts.profile-image', $contact), [
            'profile_image' => UploadedFile::fake()->image('zwei.png'),
        ])->assertRedirect();
        $second = $contact->refresh()->getRawOriginal('profile_image');

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $contact->forceDelete();

        Storage::disk('public')->assertMissing($second);
    }

    #[Test]
    public function profile_images_outside_the_crm_directory_are_never_deleted(): void
    {
        Storage::disk('public')->put('profile-photos/nutzerin.png', 'x');
        $contact = CrmContact::factory()->create(['profile_image' => 'profile-photos/nutzerin.png']);

        $contact->forceDelete();

        Storage::disk('public')->assertExists('profile-photos/nutzerin.png');
    }

    #[Test]
    public function contacts_cannot_be_created_in_mirrored_or_deleted_types(): void
    {
        $mirrored = CrmContactType::query()->firstOrCreate(
            ['slug' => CrmSystemContactTypeEnum::USER->value],
            ['name' => 'Nutzer*in', 'is_system' => true]
        );
        $deleted = CrmContactType::factory()->create();
        $deleted->delete();

        foreach ([$mirrored->id, $deleted->id] as $typeId) {
            $this->post(route('crm.contacts.store'), [
                'crm_contact_type_id' => $typeId,
                'display_name' => 'Nicht anlegbar',
            ])->assertSessionHasErrors('crm_contact_type_id');
        }

        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Nicht anlegbar']);
    }

    #[Test]
    public function contact_type_with_trashed_contacts_cannot_be_deleted(): void
    {
        $type = CrmContactType::factory()->create();
        CrmContact::factory()->create(['crm_contact_type_id' => $type->id])->delete();

        $this->delete(route('crm.types.destroy', $type));

        $this->assertNotSoftDeleted($type);
    }

    #[Test]
    public function checkbox_filter_no_includes_contacts_without_a_stored_value(): void
    {
        $type = CrmContactType::factory()->create();
        $checkbox = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::CHECKBOX->value]);
        $checked = CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);
        $unchecked = CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);
        $untouched = CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);
        foreach ([[$checked, '1'], [$unchecked, '0']] as [$contact, $value]) {
            CrmPropertyValue::query()->create([
                'crm_contact_id' => $contact->id,
                'crm_property_id' => $checkbox->id,
                'value' => $value,
            ]);
        }

        $ids = fn (string $value): array => tap(
            CrmContact::query()->where('crm_contact_type_id', $type->id),
            fn ($query) => app(CrmContactRepository::class)->applyPropertyFilters($query, [$checkbox->id => $value])
        )->pluck('id')->sort()->values()->all();

        $this->assertSame([$checked->id], $ids('1'));
        $this->assertSame(collect([$unchecked->id, $untouched->id])->sort()->values()->all(), $ids('0'));
    }

    #[Test]
    public function index_tolerates_malformed_filters_and_limits_the_page_size(): void
    {
        $type = CrmContactType::factory()->create(['is_active' => true]);
        CrmContact::factory()->count(3)->create(['crm_contact_type_id' => $type->id]);

        $this->get(route('crm.index', ['type' => $type->slug, 'filters' => ['1' => 'x'], 'per_page' => 100000]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('contacts.per_page', 200));
    }

    #[Test]
    public function renaming_a_contact_linked_to_an_artist_or_manufacturer_sticks(): void
    {
        $artist = Artist::query()->create(['name' => 'Alter Name']);
        $manufacturer = Manufacturer::query()->create(['name' => 'Alte Firma']);

        foreach ([[$artist, 'Neuer Name'], [$manufacturer, 'Neue Firma']] as [$entity, $newName]) {
            $contact = CrmContact::factory()->create([
                'display_name' => $entity->name,
                'entity_type' => $entity->getMorphClass(),
                'entity_id' => $entity->id,
            ]);

            $this->patch(route('crm.contacts.update', $contact), ['display_name' => $newName])->assertRedirect();

            $this->assertSame($newName, $contact->refresh()->display_name);
            $this->assertSame($newName, $entity->refresh()->name);
        }
    }
}
