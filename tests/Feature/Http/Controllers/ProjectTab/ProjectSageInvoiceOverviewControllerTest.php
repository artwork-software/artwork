<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectSageInvoiceOverviewControllerTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sage.enabled' => true]);
        SageApiSettings::query()->create([
            'host' => 'https://sage.example',
            'endpoint' => '/api',
            'user' => 'sage',
            'password' => 'secret',
            'enabled' => true,
        ]);
    }

    #[Test]
    public function guest_cannot_access_sage_invoice_overview(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.sage-invoices', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function user_without_project_access_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertForbidden();
    }

    #[Test]
    public function admin_gets_project_bookings_sorted_by_cost_center_and_account(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        BudgetManagementAccount::factory()->create(['account_number' => '4000', 'title' => 'Honorare']);

        // Absichtlich in "falscher" Reihenfolge angelegt; lexikografisch käme "12000" vor "4000"
        $this->createBooking($project, ['kst_stelle' => '900', 'sa_kto' => '4000', 'kreditor' => 'C']);
        $this->createBooking($project, ['kst_stelle' => '12000', 'sa_kto' => '6000', 'kreditor' => 'D']);
        $this->createBooking($project, ['kst_stelle' => '900', 'sa_kto' => '12000', 'kreditor' => 'B']);
        $this->createBooking($project, ['kst_stelle' => '400', 'sa_kto' => '4000', 'kreditor' => 'A']);
        // Fremdes Projekt darf nicht auftauchen
        $this->createBooking($otherProject, ['kst_stelle' => '100', 'sa_kto' => '4000', 'kreditor' => 'X']);

        $response = $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertOk()
            ->assertJsonPath('sage_enabled', true)
            ->assertJsonPath('access.budget', true)
            ->assertJsonPath('access.sage', true)
            ->assertJsonCount(4, 'rows');

        $this->assertSame(['A', 'C', 'B', 'D'], array_column($response->json('rows'), 'kreditor'));
        $this->assertSame('Honorare', $response->json('rows.0.sa_kto_title'));
        $this->assertNull($response->json('rows.2.sa_kto_title'));
        $this->assertSame(40.0, (float) $response->json('total'));
    }

    #[Test]
    public function collective_bookings_are_returned_once_with_their_children(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $parent = $this->createBooking($project, [
            'is_collective_booking' => true,
            'kreditor' => 'Sammel',
            'buchungsbetrag' => 30,
        ]);
        $this->createBooking($project, ['parent_booking_id' => $parent->id, 'kreditor' => 'Kind 1', 'buchungsbetrag' => 10]);
        $this->createBooking($project, ['parent_booking_id' => $parent->id, 'kreditor' => 'Kind 2', 'buchungsbetrag' => 20]);

        $response = $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertOk()
            ->assertJsonCount(1, 'rows')
            ->assertJsonCount(2, 'rows.0.find_children');

        $this->assertSame('Sammel', $response->json('rows.0.kreditor'));
        $this->assertSame(30.0, (float) $response->json('total'));
    }

    #[Test]
    public function team_member_without_sage_permission_gets_access_flags_but_no_rows(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['access_budget' => true, 'is_manager' => false, 'can_write' => true]);
        $this->createBooking($project);

        $this->actingAs($user);

        $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertOk()
            ->assertJsonPath('access.budget', true)
            ->assertJsonPath('access.sage', false)
            ->assertJsonCount(0, 'rows');
    }

    #[Test]
    public function team_member_without_budget_access_gets_no_rows(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value);
        $project->users()->attach($user->id, ['access_budget' => false, 'is_manager' => false, 'can_write' => true]);
        $this->createBooking($project);

        $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertOk()
            ->assertJsonPath('access.budget', false)
            ->assertJsonPath('access.sage', true)
            ->assertJsonCount(0, 'rows');
    }

    #[Test]
    public function team_member_with_budget_access_and_sage_permission_gets_rows(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value);
        $project->users()->attach($user->id, ['access_budget' => true, 'is_manager' => false, 'can_write' => true]);
        $this->createBooking($project);

        $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertOk()
            ->assertJsonCount(1, 'rows');
    }

    #[Test]
    public function disabled_sage_interface_returns_no_rows(): void
    {
        SageApiSettings::query()->update(['enabled' => false]);
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $this->createBooking($project);

        $this->getJson(route('projects.tabs.sage-invoices', $project))
            ->assertOk()
            ->assertJsonPath('sage_enabled', false)
            ->assertJsonCount(0, 'rows');
    }

    private function createBooking(Project $project, array $attributes = []): SageAssignedData
    {
        $cell = ColumnCell::factory()->create();
        $cell->subPositionRow->subPosition->mainPosition->table->update([
            'project_id' => $project->id,
            'is_template' => false,
        ]);

        static $sageId = 1000;

        return SageAssignedData::query()->create(array_merge([
            'column_cell_id' => $cell->id,
            'sage_id' => ++$sageId,
            'tan' => 1,
            'periode' => 202601,
            'kto_haben' => '1600',
            'kreditor' => 'Kreditor',
            'buchungstext' => 'Rechnung',
            'buchungsbetrag' => 10,
            'belegnummer' => 'RE-' . $sageId,
            'belegdatum' => '2026-01-15',
            'kto_soll' => '4000',
            'sa_kto' => '4000',
            'kst_traeger' => '1',
            'kst_stelle' => '100',
            'buchungsdatum' => '2026-01-16',
            'is_collective_booking' => false,
            'parent_booking_id' => null,
        ], $attributes));
    }
}
