<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class DocumentRequestControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_document_requests_index(): void
    {
        $this->get(route('document-requests.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_document_requests_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('document-requests.index'));

        $response->assertOk();
    }

    #[Test]
    public function index_lists_open_requests_assigned_to_others_for_users_with_edit_permission(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::DOCUMENT_REQUEST_EDIT->value);
        $requester = User::factory()->create();
        $other = User::factory()->create();

        // sichtbar: offen, an andere Person zugewiesen, egal von wem erstellt
        $foreignOpen = DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $other->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        $ownCreatedForOther = DocumentRequest::create([
            'requester_id' => $viewer->id,
            'requested_id' => $other->id,
            'status' => DocumentRequest::STATUS_IN_PROGRESS,
        ]);

        // nicht sichtbar: mir zugewiesen, niemandem zugewiesen, bereits erledigt
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $viewer->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => null,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $other->id,
            'status' => DocumentRequest::STATUS_COMPLETED,
        ]);

        $this->get(route('document-requests.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DocumentRequests/Index')
                ->has('assignedToOthersRequests', 2)
                ->where(
                    'assignedToOthersRequests',
                    fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
                        === collect([$foreignOpen->id, $ownCreatedForOther->id])->sort()->values()->all()
                )
                ->where('assignedToOthersRequests.0.requester.id', $requester->id)
                ->where('assignedToOthersRequests.0.requested.id', $other->id));
    }

    #[Test]
    public function index_hides_requests_assigned_to_others_without_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $requester = User::factory()->create();
        $other = User::factory()->create();

        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $other->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        $this->get(route('document-requests.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DocumentRequests/Index')
                ->has('assignedToOthersRequests', 0));
    }

    #[Test]
    public function guest_cannot_store_document_request(): void
    {
        $this->post(route('document-requests.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_document_request(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->post(route('document-requests.store'), [
            'contract_partner' => 'Test Partner',
            'ksk_liable' => false,
            'foreign_tax' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('document_requests', [
            'requester_id' => $admin->id,
            'contract_partner' => 'Test Partner',
        ]);
    }

    #[Test]
    public function admin_can_destroy_document_request(): void
    {
        $admin = $this->actingAsAdmin();
        $documentRequest = DocumentRequest::create([
            'requester_id' => $admin->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        $response = $this->delete(route('document-requests.destroy', $documentRequest));

        $response->assertRedirect();
        $this->assertSoftDeleted('document_requests', ['id' => $documentRequest->id]);
    }

    #[Test]
    public function unassigned_requests_are_only_sent_to_users_who_may_see_foreign_requests(): void
    {
        DocumentRequest::factory()->create(['requested_id' => null, 'status' => DocumentRequest::STATUS_OPEN]);

        // Vorher in den Props für alle, das Frontend blendete nur den Tab aus
        $this->actingAs(User::factory()->create());
        $this->get(route('document-requests.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('unassignedRequests', 0));

        $this->actingAsUserWith(PermissionEnum::DOCUMENT_REQUEST_EDIT->value);
        $this->get(route('document-requests.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where(
                'unassignedRequests',
                fn ($requests) => count($requests) >= 1
            ));
    }

    #[Test]
    public function crm_contact_data_is_only_returned_to_involved_or_authorised_users(): void
    {
        $requester = User::factory()->create();
        $request = DocumentRequest::factory()->create(['requester_id' => $requester->id, 'requested_id' => null]);

        // Vorher ohne jede Prüfung abrufbar
        $this->actingAs(User::factory()->create());
        $this->getJson(route('document-requests.crm-contact', $request))->assertForbidden();

        $this->actingAs($requester);
        $this->getJson(route('document-requests.crm-contact', $request))->assertOk();

        $this->actingAsUserWith(PermissionEnum::DOCUMENT_REQUEST_EDIT->value);
        $this->getJson(route('document-requests.crm-contact', $request))->assertOk();
    }

    #[Test]
    public function crm_contact_data_hides_values_of_confidential_groups_without_release(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Künstler*in', 'slug' => 'docreq-' . uniqid()]);
        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Ada Vertraulich',
            'is_active' => true,
        ]);

        $publicGroup = CrmPropertyGroup::query()->create(['name' => 'Öffentlich', 'is_confidential' => false]);
        $publicProperty = CrmProperty::query()->create([
            'crm_property_group_id' => $publicGroup->id,
            'name' => 'Stadt',
            'type' => 'text',
        ]);
        $confidentialGroup = CrmPropertyGroup::query()->create(['name' => 'Honorar', 'is_confidential' => true]);
        $confidentialProperty = CrmProperty::query()->create([
            'crm_property_group_id' => $confidentialGroup->id,
            'name' => 'Stundensatz',
            'type' => 'text',
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $publicProperty->id,
            'value' => 'Hamburg',
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $confidentialProperty->id,
            'value' => '95 EUR',
        ]);

        $requester = User::factory()->create();
        $request = DocumentRequest::factory()->create([
            'requester_id' => $requester->id,
            'requested_id' => null,
            'crm_contact_id' => $contact->id,
        ]);

        // Vorher lag der Stundensatz im JSON, nur die Gruppe war ausgeblendet
        $this->actingAs($requester);
        $response = $this->getJson(route('document-requests.crm-contact', $request))->assertOk();
        $values = collect($response->json('contact.property_values'))->pluck('value', 'crm_property_id');
        $this->assertSame('Hamburg', $values->get($publicProperty->id));
        $this->assertFalse($values->has($confidentialProperty->id));
        $this->assertStringNotContainsString('95 EUR', $response->getContent());

        // CRM-Verwaltung sieht vertrauliche Gruppen und deren Werte weiterhin
        $this->actingAsUserWith([
            PermissionEnum::DOCUMENT_REQUEST_EDIT->value,
            PermissionEnum::CRM_MANAGER->value,
        ]);
        $response = $this->getJson(route('document-requests.crm-contact', $request))->assertOk();
        $values = collect($response->json('contact.property_values'))->pluck('value', 'crm_property_id');
        $this->assertSame('95 EUR', $values->get($confidentialProperty->id));
        $this->assertSame('Hamburg', $values->get($publicProperty->id));
    }
}
