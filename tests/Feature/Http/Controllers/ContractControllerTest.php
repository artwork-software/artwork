<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Contract\Services\ContractService;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_contracts_index(): void
    {
        $this->get(route('contracts.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_contracts_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('contracts.index'));

        $response->assertOk();
    }

    /**
     * Projektleitungen erscheinen in der Übersicht, sind aber keine gespeicherte Freigabe: das Bearbeiten-Modal
     * belegt sich mit accessibleUsers vor und schickt die Liste beim Speichern zurück.
     */
    #[Test]
    public function contract_overview_separates_stored_shares_from_displayed_project_managers(): void
    {
        $project = Project::factory()->create();
        $manager = User::factory()->create();
        $project->users()->attach($manager->id, ['is_manager' => true]);
        $sharedUser = User::factory()->create();
        $contract = Contract::factory()->create([
            'project_id' => $project->id,
            'name' => 'Overview contract',
            'deadline_date' => '2026-10-06',
        ]);
        $contract->accessingUsers()->attach($sharedUser->id);
        $this->actingAsAdmin();

        $row = collect($this->get(route('contracts.index'))->assertOk()->viewData('page')['props']['contracts'])
            ->firstWhere('name', 'Overview contract');

        $this->assertSame([$sharedUser->id], collect($row['accessibleUsers'])->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$sharedUser->id, $manager->id],
            collect($row['displayedAccessUsers'])->pluck('id')->all()
        );
        // Kalenderdatum statt UTC-Zeitpunkt des Vortags (Filter und Bearbeiten-Modal lesen Y-m-d)
        $this->assertSame('2026-10-06', $row['deadline_date']);
    }

    /**
     * Die Übersicht filtert im Browser: "Auslandsteuer" liest foreign_tax, die Aufgaben kommen
     * vorab geladen mit.
     */
    #[Test]
    public function contract_overview_ships_the_foreign_tax_flag_and_tasks(): void
    {
        $contract = Contract::factory()->create(['name' => 'Tax contract', 'foreign_tax' => true]);
        Contract::factory()->create(['name' => 'Plain contract', 'foreign_tax' => false]);
        Task::factory()->create(['contract_id' => $contract->id, 'name' => 'Vertrag gegenzeichnen', 'done' => false]);
        Task::factory()->create(['contract_id' => $contract->id, 'name' => 'Erledigt', 'done' => true]);
        $this->actingAsAdmin();

        $rows = collect($this->get(route('contracts.index'))->assertOk()->viewData('page')['props']['contracts']);
        $taxRow = $rows->firstWhere('name', 'Tax contract');

        $this->assertTrue($taxRow['foreign_tax']);
        $this->assertFalse($rows->firstWhere('name', 'Plain contract')['foreign_tax']);
        $this->assertSame(1, $taxRow['open_tasks_count']);
        $this->assertSame(0, $rows->firstWhere('name', 'Plain contract')['open_tasks_count']);
        // Kommentare, Aufgaben und Ersteller:in lädt erst das Bearbeiten-Fenster nach
        $this->assertArrayNotHasKey('comments', $taxRow);
        $this->assertArrayNotHasKey('tasks', $taxRow);
        $this->assertArrayNotHasKey('creator', $taxRow);
    }

    #[Test]
    public function contract_details_deliver_the_full_contract_for_the_edit_dialog(): void
    {
        $contract = Contract::factory()->create(['name' => 'Detail contract']);
        Task::factory()->create(['contract_id' => $contract->id, 'name' => 'Vertrag gegenzeichnen']);
        $sharedUser = User::factory()->create();
        $contract->accessingUsers()->attach($sharedUser->id);
        $this->actingAsAdmin();

        $payload = $this->getJson(route('contracts.details', $contract))->assertOk()->json('contract');

        $this->assertSame('Detail contract', $payload['name']);
        $this->assertSame(['Vertrag gegenzeichnen'], array_column($payload['tasks'], 'name'));
        $this->assertSame([$sharedUser->id], array_column($payload['accessibleUsers'], 'id'));
        $this->assertArrayHasKey('comments', $payload);
    }

    #[Test]
    public function contract_details_and_preview_are_forbidden_without_access(): void
    {
        $contract = Contract::factory()->create(['basename' => 'fremd.pdf']);
        $this->actingAs(User::factory()->create());

        $this->getJson(route('contracts.details', $contract))->assertForbidden();
        $this->get(route('contracts.preview', $contract))->assertForbidden();
    }

    #[Test]
    public function preview_shows_pdfs_inline_and_refuses_other_file_types(): void
    {
        Storage::fake();
        Storage::put('contracts/vertrag.pdf', '%PDF-1.4 test');
        Storage::put('contracts/seite.html', '<script>alert(1)</script>');
        $pdf = Contract::factory()->create(['basename' => 'vertrag.pdf', 'name' => 'Vertrag "final".pdf']);
        $html = Contract::factory()->create(['basename' => 'seite.html', 'name' => 'seite.html']);
        $missing = Contract::factory()->create(['basename' => 'weg.pdf', 'name' => 'weg.pdf']);
        $this->actingAsAdmin();

        $response = $this->get(route('contracts.preview', $pdf))->assertOk();
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('content-disposition'));
        $this->assertSame('nosniff', $response->headers->get('x-content-type-options'));

        // alte Datei mit HTML-Inhalt unter PDF-Namen: der Typ folgt der Endung, nie dem Inhalt
        Storage::put('contracts/getarnt.pdf', '<html><script>alert(1)</script></html>');
        $disguised = Contract::factory()->create(['basename' => 'getarnt.pdf', 'name' => 'getarnt.pdf']);
        $this->assertSame(
            'application/pdf',
            $this->get(route('contracts.preview', $disguised))->assertOk()->headers->get('content-type')
        );

        $this->get(route('contracts.preview', $html))->assertStatus(415);
        $this->get(route('contracts.preview', $missing))->assertNotFound();

        $rows = collect($this->get(route('contracts.index'))->assertOk()->viewData('page')['props']['contracts']);
        $this->assertTrue($rows->firstWhere('id', $pdf->id)['is_previewable']);
        $this->assertFalse($rows->firstWhere('id', $html->id)['is_previewable']);
    }

    #[Test]
    public function bulk_delete_removes_all_given_contracts(): void
    {
        $first = Contract::factory()->create();
        $second = Contract::factory()->create();
        $kept = Contract::factory()->create();
        $this->actingAsAdmin();

        $this->postJson(route('contracts.bulk-delete'), ['ids' => [$first->id, $second->id]])
            ->assertOk()
            ->assertExactJson(['deleted' => 2]);

        $this->assertNull(Contract::find($first->id));
        $this->assertNull(Contract::find($second->id));
        $this->assertNotNull(Contract::find($kept->id));
    }

    #[Test]
    public function bulk_delete_deletes_nothing_when_one_contract_is_not_accessible(): void
    {
        $user = User::factory()->create();
        $own = Contract::factory()->create(['creator_id' => $user->id]);
        $foreign = Contract::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('contracts.bulk-delete'), ['ids' => [$own->id, $foreign->id]])->assertForbidden();
        $this->postJson(route('contracts.bulk-delete'), ['ids' => [$own->id, 999999]])->assertNotFound();
        $this->postJson(route('contracts.bulk-delete'), ['ids' => []])->assertUnprocessable();

        $this->assertNotNull(Contract::find($own->id));
        $this->assertNotNull(Contract::find($foreign->id));
    }

    #[Test]
    public function export_can_be_limited_to_selected_contracts(): void
    {
        $admin = $this->actingAsAdmin();
        $selected = Contract::factory()->create();
        Contract::factory()->create();

        $contracts = app(ContractService::class)->contractsForExport(['ids' => [$selected->id]], $admin);

        $this->assertSame([$selected->id], $contracts->pluck('id')->all());
    }

    #[Test]
    public function contract_overview_handles_contracts_without_project(): void
    {
        Contract::factory()->create(['project_id' => null, 'name' => 'Loose contract']);
        $this->actingAsAdmin();

        $row = collect($this->get(route('contracts.index'))->assertOk()->viewData('page')['props']['contracts'])
            ->firstWhere('name', 'Loose contract');

        $this->assertCount(0, collect($row['displayedAccessUsers']));
        $this->assertCount(0, collect($row['accessibleUsers']));
    }

    #[Test]
    public function guest_cannot_save_filter(): void
    {
        $this->post(route('contracts.filter.save'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_save_filter(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('contracts.filter.save'), [
            'kskLiable' => true,
            'foreignTax' => false,
            'dateFrom' => null,
            'dateTo' => null,
            'legalFormIds' => [],
            'contractTypeIds' => [],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
