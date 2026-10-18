<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Duplikat-Übersicht (crm.duplicates) und des
 * Excel-Exports (crm.export) fest; beide nur für `crm manager`.
 */
final class CrmDuplicatesAndExportTest extends FeatureTestCase
{
    #[Test]
    public function duplicates_page_groups_contacts_with_same_name_per_type(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();
        $first = CrmContact::factory()->create(['crm_contact_type_id' => $type->id, 'display_name' => 'Anna Beispiel']);
        $second = CrmContact::factory()->create(['crm_contact_type_id' => $type->id, 'display_name' => ' anna beispiel ']);
        CrmContact::factory()->create(['display_name' => 'Anna Beispiel']);

        $this->get(route('crm.duplicates'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CRM/Duplicates')
                ->has('duplicateGroups', 1)
                ->where('duplicateGroups.0.match', 'name')
                ->where('duplicateGroups.0.value', 'anna beispiel')
                ->where('duplicateGroups.0.contact_type.id', $type->id)
                ->has('duplicateGroups.0.contacts', 2)
                ->where('duplicateGroups.0.contacts.0.id', $first->id)
                ->where('duplicateGroups.0.contacts.1.id', $second->id)
                ->where('duplicateGroups.0.contacts.0.has_entity', false));
    }

    #[Test]
    public function duplicates_page_ignores_mirrored_contact_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $mirrored = CrmContactType::factory()->create(['slug' => CrmSystemContactTypeEnum::USER->value]);
        CrmContact::factory()->count(2)->create(['crm_contact_type_id' => $mirrored->id, 'display_name' => 'Gleich']);

        $this->get(route('crm.duplicates'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('duplicateGroups', 0));
    }

    #[Test]
    public function duplicates_page_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->get(route('crm.duplicates'))->assertForbidden();
    }

    #[Test]
    public function export_downloads_an_xlsx_file_for_crm_manager(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();
        CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);

        $response = $this->post(route('crm.export'), [
            'columns' => ['display_name'],
            'contact_type_ids' => [$type->id],
        ]);

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertMatchesRegularExpression(
            '/attachment; filename=crm-export-\d{4}-\d{2}-\d{2}_\d{6}\.xlsx/',
            (string) $response->headers->get('content-disposition')
        );
    }

    #[Test]
    public function export_validates_columns_and_date_range(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->post(route('crm.export'), [
            'contact_type_ids' => [999999],
            'date_from' => '2026-02-01',
            'date_to' => '2026-01-01',
        ])->assertSessionHasErrors(['columns', 'contact_type_ids.0', 'date_to']);
    }

    #[Test]
    public function export_is_forbidden_for_crm_viewer_and_plain_user(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->post(route('crm.export'), ['columns' => ['display_name']])->assertForbidden();

        $this->actingAs(User::factory()->create());
        $this->post(route('crm.export'), ['columns' => ['display_name']])->assertForbidden();
    }
}
