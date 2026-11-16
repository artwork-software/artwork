<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Models\ContractModule;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für Vertragsbausteine, das Hochladen einer Vertragsdatei
 * und das Verknüpfen einer Dokumentanfrage mit einem Vertrag.
 */
final class ContractUploadsAndDocumentRequestLinkTest extends FeatureTestCase
{
    #[Test]
    public function a_contract_module_is_stored_with_original_name_and_file(): void
    {
        $this->actingAsAdmin();

        $this->from('/contracts')->post(route('contracts.module.store'), [
            'file' => UploadedFile::fake()->create('Mustervertrag.pdf', 20, 'application/pdf'),
        ])->assertRedirect('/contracts');

        $module = ContractModule::query()->where('name', 'Mustervertrag.pdf')->firstOrFail();
        $this->assertNotSame('Mustervertrag.pdf', $module->basename);
        Storage::disk('local')->assertExists('contract_modules/' . $module->basename);
    }

    #[Test]
    public function storing_a_contract_module_without_file_is_a_client_error(): void
    {
        $this->actingAsAdmin();
        $before = ContractModule::query()->count();

        $this->post(route('contracts.module.store'))->assertStatus(400);

        $this->assertSame($before, ContractModule::query()->count());
    }

    #[Test]
    public function destroying_a_contract_module_removes_record_and_stored_file(): void
    {
        $this->actingAsAdmin();
        Storage::disk('local')->put('contract_modules/baustein.pdf', 'pdf');
        $module = ContractModule::query()->create(['name' => 'Baustein.pdf', 'basename' => 'baustein.pdf']);

        $this->delete('/contract_modules/' . $module->id)->assertRedirect(route('contracts.index'));

        $this->assertModelMissing($module);
        Storage::disk('local')->assertMissing('contract_modules/baustein.pdf');
    }

    #[Test]
    public function destroying_a_contract_module_whose_file_is_gone_still_works(): void
    {
        $this->actingAsAdmin();
        $module = ContractModule::query()->create(['name' => 'Weg.pdf', 'basename' => 'weg.pdf']);

        $this->delete('/contract_modules/' . $module->id)->assertRedirect(route('contracts.index'));

        $this->assertModelMissing($module);
    }

    #[Test]
    public function a_contract_file_is_stored_on_the_contract(): void
    {
        $this->actingAsAdmin();
        $contract = Contract::factory()->create(['name' => 'Alt', 'basename' => '']);

        $this->post(route('contracts.store.file'), [
            'contract' => $contract->id,
            'file' => UploadedFile::fake()->create('Gastspielvertrag.pdf', 20, 'application/pdf'),
        ])->assertOk();

        $contract->refresh();
        $this->assertSame('Gastspielvertrag.pdf', $contract->name);
        $this->assertNotSame('', $contract->basename);
        Storage::disk('local')->assertExists('contracts/' . $contract->basename);
    }

    #[Test]
    public function storing_a_contract_file_without_file_is_a_validation_error_not_a_server_error(): void
    {
        $this->actingAsAdmin();
        $contract = Contract::factory()->create(['name' => 'Alt', 'basename' => 'alt.pdf']);

        $this->postJson(route('contracts.store.file'), ['contract' => $contract->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertSame('alt.pdf', $contract->fresh()->basename);
    }

    #[Test]
    public function storing_a_contract_file_for_an_unknown_contract_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('contracts.store.file'), [
            'contract' => 99999999,
            'file' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('contract');
    }

    #[Test]
    public function linking_a_contract_completes_the_document_request(): void
    {
        $requester = User::factory()->create();
        $requested = User::factory()->create();
        $this->actingAsAdmin();
        $contract = Contract::factory()->create();
        $documentRequest = DocumentRequest::factory()->create([
            'requester_id' => $requester->id,
            'requested_id' => $requested->id,
            'project_id' => $contract->project_id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        $this->from('/requests')
            ->post(route('document-requests.link-contract', $documentRequest), ['contract_id' => $contract->id])
            ->assertRedirect('/requests');

        $documentRequest->refresh();
        $this->assertSame($contract->id, $documentRequest->contract_id);
        $this->assertSame(DocumentRequest::STATUS_COMPLETED, $documentRequest->status);
    }

    #[Test]
    public function linking_a_contract_without_assigned_person_or_project_still_works(): void
    {
        $this->actingAsAdmin();
        $contract = Contract::factory()->create();
        $documentRequest = DocumentRequest::factory()->create([
            'requested_id' => null,
            'project_id' => null,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        $this->post(route('document-requests.link-contract', $documentRequest), ['contract_id' => $contract->id])
            ->assertRedirect();

        $this->assertSame(DocumentRequest::STATUS_COMPLETED, $documentRequest->fresh()->status);
    }

    #[Test]
    public function linking_requires_an_existing_contract(): void
    {
        $this->actingAsAdmin();
        $documentRequest = DocumentRequest::factory()->create(['status' => DocumentRequest::STATUS_OPEN]);

        $this->postJson(route('document-requests.link-contract', $documentRequest), ['contract_id' => 99999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contract_id');

        $documentRequest->refresh();
        $this->assertNull($documentRequest->contract_id);
        $this->assertSame(DocumentRequest::STATUS_OPEN, $documentRequest->status);
    }
}
