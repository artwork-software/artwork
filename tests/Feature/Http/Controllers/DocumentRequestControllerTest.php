<?php

namespace Tests\Feature\Http\Controllers;

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
}
