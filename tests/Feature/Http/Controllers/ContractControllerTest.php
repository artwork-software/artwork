<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
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
