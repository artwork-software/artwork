<?php

namespace Tests\Feature;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

final class CrmImportSessionTest extends FeatureTestCase
{
    private function makeContactType(): array
    {
        $group = CrmPropertyGroup::create(['name' => 'Import Test Gruppe']);

        $type = CrmContactType::create([
            'name' => 'Import Test Typ',
            'slug' => 'import-test-typ',
            'is_system' => false,
            'is_active' => true,
        ]);

        $props = [];
        foreach (['Vorname', 'Nachname', 'Email'] as $i => $name) {
            $props[$name] = CrmProperty::create([
                'crm_property_group_id' => $group->id,
                'name' => $name,
                'type' => 'text',
                'sort_order' => $i,
            ]);
        }

        $type->properties()->attach(
            collect($props)->mapWithKeys(fn ($p) => [$p->id => ['sort_order' => 0]])->toArray()
        );

        return [$type, $props];
    }

    private function csvUpload(): UploadedFile
    {
        $csv = "Vorname,Nachname,Kategorie,E-Mail\n"
            . "Adriana,Almeida-Pees,,adriana@example.org\n"
            . "Muhamed,Almusibli,Direktor,muhamed@example.org\n";

        return UploadedFile::fake()->createWithContent('kontakte.csv', $csv);
    }

    private function executeMapping(array $props): array
    {
        return [
            'mapping' => [
                'display_name' => null,
                'properties' => [
                    (string) $props['Vorname']->id => 0,
                    (string) $props['Nachname']->id => 1,
                    (string) $props['Email']->id => 3,
                ],
            ],
            'duplicates' => null,
        ];
    }

    #[Test]
    public function single_type_import_creates_contacts(): void
    {
        $this->actingAsAdmin();
        [$type, $props] = $this->makeContactType();

        $this->post(route('crm.import.upload'), [
            'file' => $this->csvUpload(),
            'use_type_column' => '0',
            'crm_contact_type_id' => $type->id,
        ])->assertOk();

        $this->post(route('crm.import.execute'), $this->executeMapping($props))
            ->assertRedirect();

        $result = session('importResult');
        $this->assertSame(2, $result['created']);
        $this->assertSame([], $result['skipped']);
        $this->assertDatabaseHas('crm_contacts', [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Adriana Almeida-Pees',
        ]);
    }

    /**
     * Regression: Ein abgebrochener Typ-Spalten-Import ließ crm_import_multi_type in der
     * Session zurück; der nächste normale Import crashte dann mit
     * "Undefined array key typeId" (Sentry TANZHAUS-BASEL-36) — der Kunde bekam
     * weder Feedback noch Einträge.
     */
    #[Test]
    public function import_works_after_abandoned_type_column_attempt(): void
    {
        $this->actingAsAdmin();
        [$type, $props] = $this->makeContactType();

        // 1. Import mit aktiviertem Typ-Spalten-Toggle starten und abbrechen
        $this->post(route('crm.import.upload'), [
            'file' => $this->csvUpload(),
            'use_type_column' => '1',
        ])->assertOk();

        // 2. Neuer Anlauf ohne Typ-Spalte mit festem Kontakttyp
        $this->post(route('crm.import.upload'), [
            'file' => $this->csvUpload(),
            'use_type_column' => '0',
            'crm_contact_type_id' => $type->id,
        ])->assertOk();

        // 3. Import ausführen — darf nicht mehr am alten Multi-Typ-Flag scheitern
        $this->post(route('crm.import.execute'), $this->executeMapping($props))
            ->assertRedirect();

        $result = session('importResult');
        $this->assertNotNull($result, 'importResult flash missing');
        $this->assertSame(2, $result['created']);
        $this->assertSame(2, CrmContact::where('crm_contact_type_id', $type->id)->count());
    }
}
