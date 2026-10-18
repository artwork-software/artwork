<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Import-Zwischenschritte fest, die CrmImportSessionTest
 * nicht abdeckt: Upload-Seite, Spaltenwerte, Typ-Zuordnung (Multi-Typ-Import) und
 * Abbrechen. Upload/Ausführen selbst sind dort bereits getestet.
 */
final class CrmImportStepsTest extends FeatureTestCase
{
    private function uploadWithTypeColumn(): void
    {
        $csv = "Name,Typ\nAnna,Agentur\nBen,Presse\nCleo,Agentur\n";

        $this->post(route('crm.import.upload'), [
            'file' => UploadedFile::fake()->createWithContent('kontakte.csv', $csv),
            'use_type_column' => '1',
        ])->assertOk();
    }

    #[Test]
    public function upload_page_lists_only_active_non_mirrored_contact_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $importable = CrmContactType::factory()->create();
        CrmContactType::factory()->create(['is_active' => false]);
        CrmContactType::factory()->create(['slug' => CrmSystemContactTypeEnum::FREELANCER->value]);

        $this->get(route('crm.import'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CRM/Import/Upload')
                ->has('contactTypes', 1)
                ->where('contactTypes.0.id', $importable->id)
                ->has('propertyGroups'));
    }

    #[Test]
    public function upload_page_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->get(route('crm.import'))->assertForbidden();
    }

    #[Test]
    public function column_values_without_import_session_returns_422(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->getJson(route('crm.import.column-values', 1))
            ->assertStatus(422)
            ->assertJson(['error' => 'No active import session']);
    }

    #[Test]
    public function column_values_returns_unique_values_of_uploaded_file(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->uploadWithTypeColumn();

        $values = $this->getJson(route('crm.import.column-values', 1))
            ->assertOk()
            ->json('values');

        $this->assertEqualsCanonicalizing(
            ['Agentur', 'Presse'],
            collect($values)->map(fn ($entry) => is_array($entry) ? $entry['value'] : $entry)->all()
        );
    }

    #[Test]
    public function column_values_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->getJson(route('crm.import.column-values', 0))->assertForbidden();
    }

    #[Test]
    public function map_types_without_import_session_renders_upload_page_with_error(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();

        $this->post(route('crm.import.map-types'), [
            'type_column_index' => 1,
            'type_value_mapping' => [['type_value' => 'Agentur', 'crm_contact_type_id' => $type->id]],
        ])
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CRM/Import/Upload')
                ->has('error'));
    }

    #[Test]
    public function map_types_stores_mapping_and_renders_multi_mapping_page(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $agency = CrmContactType::factory()->create();
        $agency->properties()->attach(CrmProperty::factory()->create()->id, ['sort_order' => 0]);
        $mapping = [
            ['type_value' => 'Agentur', 'crm_contact_type_id' => $agency->id],
            ['type_value' => 'Presse', 'crm_contact_type_id' => null],
        ];
        $this->uploadWithTypeColumn();

        $this->post(route('crm.import.map-types'), [
            'type_column_index' => 1,
            'type_value_mapping' => $mapping,
        ])
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CRM/Import/MultiMapping')
                ->has('contactTypes', 1)
                ->where('contactTypes.0.id', $agency->id)
                ->where('headers', ['Name', 'Typ'])
                ->where('totalRows', 3)
                ->where('typeColumnIndex', 1));

        $this->assertSame(1, session('crm_import_type_column_index'));
        $this->assertCount(2, session('crm_import_type_value_mapping'));
    }

    #[Test]
    public function map_types_validates_payload_and_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->post(route('crm.import.map-types'), [
            'type_value_mapping' => [['crm_contact_type_id' => 999999]],
        ])->assertSessionHasErrors([
            'type_column_index',
            'type_value_mapping.0.type_value',
            'type_value_mapping.0.crm_contact_type_id',
        ]);

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->post(route('crm.import.map-types'), [
            'type_column_index' => 0,
            'type_value_mapping' => [['type_value' => 'x']],
        ])->assertForbidden();
    }

    #[Test]
    public function cancel_removes_uploaded_file_and_session_and_redirects_to_crm(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->uploadWithTypeColumn();
        $path = session('crm_import_path');
        Storage::disk('local')->assertExists($path);

        $this->delete(route('crm.import.cancel'))->assertRedirect(route('crm.index'));

        Storage::disk('local')->assertMissing($path);
        $this->assertNull(session('crm_import_path'));
        $this->assertNull(session('crm_import_multi_type'));
    }
}
