<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Services\CrmDuplicateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Upload-Eigenschaften im CRM: die Datei liegt unter einem Hash-Namen, der Originalname bleibt als
 * Anzeigename erhalten (Liste, Download) und lässt sich nachträglich ändern.
 */
final class CrmPropertyFileNameTest extends FeatureTestCase
{
    private const PNG_1X1_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private CrmContact $contact;

    private CrmProperty $property;

    private CrmPropertyGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestCase fakt bereits "local".
        Storage::fake('public');

        $type = CrmContactType::query()->create(['name' => 'Fotograf*innen', 'slug' => 'file-name-type']);
        $this->contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Studio Nord',
            'is_active' => true,
        ]);
        $this->group = CrmPropertyGroup::query()->create(['name' => 'Bilder', 'is_confidential' => false]);
        $this->property = CrmProperty::query()->create([
            'crm_property_group_id' => $this->group->id,
            'name' => 'Pressefoto',
            'type' => CrmPropertyTypeEnum::UPLOAD->value,
        ]);
    }

    private function pngUpload(string $clientName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'crm-file-name-');
        file_put_contents($path, base64_decode(self::PNG_1X1_BASE64));

        return new UploadedFile($path, $clientName, null, null, true);
    }

    private function upload(string $clientName): void
    {
        $this->post(
            route('crm.contacts.property-file.upload', $this->contact),
            ['property_id' => $this->property->id, 'file' => $this->pngUpload($clientName)]
        )->assertSessionHasNoErrors();
    }

    private function storedValue(): ?CrmPropertyValue
    {
        return CrmPropertyValue::query()
            ->where('crm_contact_id', $this->contact->id)
            ->where('crm_property_id', $this->property->id)
            ->first();
    }

    private function downloadUrl(): string
    {
        return route('crm.contacts.property-file.download', [
            'crmContact' => $this->contact,
            'property' => $this->property,
        ]);
    }

    #[Test]
    public function upload_keeps_the_original_file_name_while_storing_under_a_hash(): void
    {
        $this->actingAsUserWith('can view crm');

        $this->upload('Pressefoto Müller 2026.png');

        $value = $this->storedValue();
        $this->assertMatchesRegularExpression('#^crm-property-files/[a-f0-9]{32}\.png$#', $value->value);
        $this->assertSame('Pressefoto Müller 2026.png', $value->file_name);
        Storage::disk('local')->assertExists($value->value);
    }

    #[Test]
    public function download_is_offered_under_the_original_file_name(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('portrait.png');

        $this->get($this->downloadUrl())
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=portrait.png');
    }

    #[Test]
    public function replacing_the_file_takes_over_the_new_original_name(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('alt.png');
        $oldPath = $this->storedValue()->value;

        $this->upload('neu.png');

        $this->assertSame('neu.png', $this->storedValue()->file_name);
        Storage::disk('local')->assertMissing($oldPath);
    }

    #[Test]
    public function upload_without_extension_gets_the_stored_extension_appended(): void
    {
        $this->actingAsUserWith('can view crm');

        $this->upload('bild');

        $this->assertSame('bild.png', $this->storedValue()->file_name);
    }

    #[Test]
    public function rename_changes_only_the_display_name_not_the_stored_file(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('IMG_4711.png');
        $path = $this->storedValue()->value;

        $this->patch(route('crm.contacts.property-file.rename', $this->contact), [
            'property_id' => $this->property->id,
            'file_name' => 'Ensemble Probe.png',
        ])->assertSessionHasNoErrors();

        $value = $this->storedValue();
        $this->assertSame('Ensemble Probe.png', $value->file_name);
        $this->assertSame($path, $value->value);
        Storage::disk('local')->assertExists($path);
        $this->get($this->downloadUrl())
            ->assertHeader('content-disposition', 'attachment; filename="Ensemble Probe.png"');
    }

    #[Test]
    public function rename_appends_the_extension_when_it_was_removed(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('IMG_4711.png');

        $this->patch(route('crm.contacts.property-file.rename', $this->contact), [
            'property_id' => $this->property->id,
            'file_name' => '  Ensemble Probe  ',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Ensemble Probe.png', $this->storedValue()->file_name);
    }

    #[Test]
    public function rename_strips_path_separators(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('IMG_4711.png');

        $this->patch(route('crm.contacts.property-file.rename', $this->contact), [
            'property_id' => $this->property->id,
            'file_name' => '../../etc/passwd.png',
        ])->assertSessionHasNoErrors();

        $this->assertSame('....etcpasswd.png', $this->storedValue()->file_name);
    }

    #[Test]
    public function rename_rejects_a_name_without_usable_characters(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('IMG_4711.png');

        $this->patch(route('crm.contacts.property-file.rename', $this->contact), [
            'property_id' => $this->property->id,
            'file_name' => '/ % .',
        ])->assertSessionHasErrors('file_name');

        $this->assertSame('IMG_4711.png', $this->storedValue()->file_name);
    }

    #[Test]
    public function rename_without_a_stored_file_is_not_found(): void
    {
        $this->actingAsUserWith('can view crm');

        $this->patch(route('crm.contacts.property-file.rename', $this->contact), [
            'property_id' => $this->property->id,
            'file_name' => 'egal.png',
        ])->assertNotFound();
    }

    #[Test]
    public function rename_in_a_confidential_group_without_edit_right_is_forbidden(): void
    {
        $this->group->update(['is_confidential' => true]);
        $path = 'crm-property-files/' . bin2hex(random_bytes(16)) . '.png';
        Storage::disk('local')->put($path, 'png');
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $this->contact->id,
            'crm_property_id' => $this->property->id,
            'value' => $path,
            'file_name' => 'vertraulich.png',
        ]);
        $this->actingAsUserWith('can view crm');

        $this->patch(route('crm.contacts.property-file.rename', $this->contact), [
            'property_id' => $this->property->id,
            'file_name' => 'anders.png',
        ])->assertForbidden();

        $this->assertSame('vertraulich.png', $this->storedValue()->file_name);
    }

    #[Test]
    public function deleting_the_file_clears_the_file_name(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('portrait.png');

        $this->delete(route('crm.contacts.property-file.delete', $this->contact), [
            'property_id' => $this->property->id,
        ])->assertSessionHasNoErrors();

        $value = $this->storedValue();
        $this->assertNull($value->value);
        $this->assertNull($value->file_name);
    }

    #[Test]
    public function legacy_file_without_stored_name_is_downloaded_under_its_hash_name(): void
    {
        $this->actingAsUserWith('can view crm');
        $path = 'crm-property-files/' . bin2hex(random_bytes(16)) . '.png';
        Storage::disk('local')->put($path, base64_decode(self::PNG_1X1_BASE64));
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $this->contact->id,
            'crm_property_id' => $this->property->id,
            'value' => $path,
        ]);

        $this->get($this->downloadUrl())
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=' . basename($path));
    }

    #[Test]
    public function merging_duplicates_carries_the_file_name_over(): void
    {
        $this->actingAsUserWith('can view crm');
        $this->upload('portrait.png');
        $path = $this->storedValue()->value;
        $primary = CrmContact::query()->create([
            'crm_contact_type_id' => $this->contact->crm_contact_type_id,
            'display_name' => 'Studio Nord (neu)',
            'is_active' => true,
        ]);

        app(CrmDuplicateService::class)->merge($primary, collect([$this->contact]));

        $merged = CrmPropertyValue::query()
            ->where('crm_contact_id', $primary->id)
            ->where('crm_property_id', $this->property->id)
            ->first();
        $this->assertSame($path, $merged->value);
        $this->assertSame('portrait.png', $merged->file_name);
    }
}
