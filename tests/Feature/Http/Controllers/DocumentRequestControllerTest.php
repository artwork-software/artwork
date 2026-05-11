<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
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
