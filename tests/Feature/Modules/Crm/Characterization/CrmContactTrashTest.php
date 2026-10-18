<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten des CRM-Papierkorbs fest: Liste (paginiert, Suche),
 * Wiederherstellen, endgültig Löschen (einzeln und alle). Alle Routen nur für
 * `crm manager`; nicht gelöschte Kontakte sind über die Papierkorb-IDs nicht erreichbar.
 */
final class CrmContactTrashTest extends FeatureTestCase
{
    private function trashedContact(array $attributes = []): CrmContact
    {
        $contact = CrmContact::factory()->create($attributes);
        $contact->delete();

        return $contact;
    }

    #[Test]
    public function trashed_lists_only_soft_deleted_contacts_sorted_by_name(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create(['name' => 'Agentur', 'icon' => 'building', 'color' => '#123456']);
        $zeta = $this->trashedContact(['display_name' => 'Zeta', 'crm_contact_type_id' => $type->id]);
        $alpha = $this->trashedContact(['display_name' => 'Alpha', 'crm_contact_type_id' => $type->id]);
        CrmContact::factory()->create(['display_name' => 'Aktiv']);

        $this->get(route('crm.contacts.trashed'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trash/CrmContacts')
                ->has('trashed_contacts.data', 2)
                ->where('trashed_contacts.data.0.id', $alpha->id)
                ->where('trashed_contacts.data.1.id', $zeta->id)
                ->where('trashed_contacts.data.0.contact_type', [
                    'name' => 'Agentur',
                    'icon' => 'building',
                    'color' => '#123456',
                ])
                ->has('trashed_contacts.data.0.deleted_at')
                ->where('trashed_contacts.per_page', 25));
    }

    #[Test]
    public function trashed_filters_by_search_and_respects_entities_per_page(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $match = $this->trashedContact(['display_name' => 'Müller Agentur']);
        $this->trashedContact(['display_name' => 'Schmidt']);

        $this->get(route('crm.contacts.trashed', ['search' => 'ller', 'entitiesPerPage' => 10]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trashed_contacts.data', 1)
                ->where('trashed_contacts.data.0.id', $match->id)
                ->where('trashed_contacts.per_page', 10));
    }

    #[Test]
    public function trashed_is_forbidden_for_crm_viewer_and_plain_user(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->get(route('crm.contacts.trashed'))->assertForbidden();

        $this->actingAs(User::factory()->create());
        $this->get(route('crm.contacts.trashed'))->assertForbidden();
    }

    #[Test]
    public function restore_brings_back_a_trashed_contact(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = $this->trashedContact();

        $this->from(route('crm.contacts.trashed'))
            ->patch(route('crm.contacts.restore', $contact->id))
            ->assertRedirect(route('crm.contacts.trashed'));

        $this->assertNotSoftDeleted('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function restore_returns_404_for_contacts_not_in_trash(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create();

        $this->patch(route('crm.contacts.restore', $contact->id))->assertNotFound();
        $this->patch(route('crm.contacts.restore', 999999))->assertNotFound();
    }

    #[Test]
    public function restore_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $contact = $this->trashedContact();

        $this->patch(route('crm.contacts.restore', $contact->id))->assertForbidden();

        $this->assertSoftDeleted('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function force_delete_removes_a_trashed_contact_permanently(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = $this->trashedContact();

        $this->delete(route('crm.contacts.force', $contact->id))->assertRedirect();

        $this->assertDatabaseMissing('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function force_delete_returns_404_for_contacts_not_in_trash(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create();

        $this->delete(route('crm.contacts.force', $contact->id))->assertNotFound();

        $this->assertNotSoftDeleted('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function force_delete_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $contact = $this->trashedContact();

        $this->delete(route('crm.contacts.force', $contact->id))->assertForbidden();

        $this->assertSoftDeleted('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function force_delete_all_purges_only_trashed_contacts(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $first = $this->trashedContact();
        $second = $this->trashedContact();
        $active = CrmContact::factory()->create();

        $this->delete(route('crm.contacts.force.all'))->assertRedirect();

        $this->assertDatabaseMissing('crm_contacts', ['id' => $first->id]);
        $this->assertDatabaseMissing('crm_contacts', ['id' => $second->id]);
        $this->assertNotSoftDeleted('crm_contacts', ['id' => $active->id]);
    }

    #[Test]
    public function force_delete_all_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $contact = $this->trashedContact();

        $this->delete(route('crm.contacts.force.all'))->assertForbidden();

        $this->assertSoftDeleted('crm_contacts', ['id' => $contact->id]);
    }
}
