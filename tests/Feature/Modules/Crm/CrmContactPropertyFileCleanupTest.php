<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

/**
 * Endgültiges Löschen eines CRM-Kontakts entfernt die Dateien seiner Upload-Eigenschaften von
 * local- und public-Disk (DSGVO); Soft-Delete lässt sie für die Wiederherstellung liegen.
 */
final class CrmContactPropertyFileCleanupTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestCase fakt bereits "local".
        Storage::fake('public');
    }

    private function uploadProperty(): CrmProperty
    {
        return CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::UPLOAD->value]);
    }

    private function storePropertyFile(
        CrmContact $contact,
        CrmProperty $property,
        string $disk = 'local',
        string $storedValuePrefix = ''
    ): string {
        $path = 'crm-property-files/' . bin2hex(random_bytes(16)) . '.pdf';
        Storage::disk($disk)->put($path, '%PDF-1.4');

        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            'value' => $storedValuePrefix . $path,
        ]);

        return $path;
    }

    #[Test]
    public function force_delete_removes_upload_files_from_local_and_public_disk(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create();
        $privateFile = $this->storePropertyFile($contact, $this->uploadProperty());
        // Altbestand: Datei noch auf der public-Disk, Wert mit "/storage/"-Präfix gespeichert
        $legacyFile = $this->storePropertyFile($contact, $this->uploadProperty(), 'public', '/storage/');
        $contact->delete();

        $this->delete(route('crm.contacts.force', $contact->id))->assertRedirect();

        $this->assertDatabaseMissing('crm_contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id]);
        Storage::disk('local')->assertMissing($privateFile);
        Storage::disk('public')->assertMissing($legacyFile);
    }

    #[Test]
    public function force_delete_all_removes_upload_files_of_every_trashed_contact_only(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = $this->uploadProperty();
        $first = CrmContact::factory()->create();
        $second = CrmContact::factory()->create();
        $active = CrmContact::factory()->create();
        $firstFile = $this->storePropertyFile($first, $property);
        $secondFile = $this->storePropertyFile($second, $property);
        $activeFile = $this->storePropertyFile($active, $property);
        $first->delete();
        $second->delete();

        $this->delete(route('crm.contacts.force.all'))->assertRedirect();

        Storage::disk('local')->assertMissing($firstFile);
        Storage::disk('local')->assertMissing($secondFile);
        Storage::disk('local')->assertExists($activeFile);
    }

    #[Test]
    public function soft_delete_keeps_upload_files_so_restore_still_works(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create();
        $property = $this->uploadProperty();
        $file = $this->storePropertyFile($contact, $property);

        $contact->delete();

        $this->assertSoftDeleted('crm_contacts', ['id' => $contact->id]);
        Storage::disk('local')->assertExists($file);

        $this->patch(route('crm.contacts.restore', $contact->id))->assertRedirect();

        $this->assertNotSoftDeleted('crm_contacts', ['id' => $contact->id]);
        $this->assertDatabaseHas('crm_property_values', ['crm_contact_id' => $contact->id, 'value' => $file]);
        Storage::disk('local')->assertExists($file);
    }

    #[Test]
    public function force_delete_leaves_files_referenced_by_non_upload_properties_and_outside_the_directory(): void
    {
        $contact = CrmContact::factory()->create();
        $textProperty = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::TEXT->value]);
        $textReferencedFile = $this->storePropertyFile($contact, $textProperty);

        $foreignFile = 'profile-photos/' . bin2hex(random_bytes(16)) . '.png';
        Storage::disk('public')->put($foreignFile, 'png');
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $this->uploadProperty()->id,
            'value' => $foreignFile,
        ]);

        $contact->forceDelete();

        Storage::disk('local')->assertExists($textReferencedFile);
        Storage::disk('public')->assertExists($foreignFile);
    }

    #[Test]
    public function force_delete_keeps_files_when_the_surrounding_transaction_rolls_back(): void
    {
        $contact = CrmContact::factory()->create();
        $file = $this->storePropertyFile($contact, $this->uploadProperty());

        try {
            DB::transaction(function () use ($contact): void {
                $contact->forceDelete();

                throw new RuntimeException('Abbruch');
            });
        } catch (RuntimeException) {
            // erwarteter Abbruch
        }

        $this->assertDatabaseHas('crm_contacts', ['id' => $contact->id]);
        Storage::disk('local')->assertExists($file);
    }
}
