<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectBudgetInformationControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_budget_information_tab(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.budget-informations', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_budget_information_tab(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.budget-informations', $project));

        $response->assertOk();
    }

    #[Test]
    public function user_without_project_access_is_redirected_back(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->get(route('projects.tabs.budget-informations', $project));

        $response->assertStatus(302);
    }

    #[Test]
    public function team_member_without_budget_rights_gets_no_files_contracts_or_money_sources(): void
    {
        $project = $this->projectWithBudgetData();
        $member = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => true]);
        $this->actingAs($member);

        $info = $this->getJson(route('projects.tabs.budget-informations', $project))
            ->assertOk()
            ->json('BudgetInformation');

        $this->assertSame([], $info['project_files']);
        $this->assertSame([], $info['contracts']);
        $this->assertSame([], $info['project_money_sources']);
    }

    #[Test]
    public function budget_access_shows_money_sources_and_only_contracts_and_files_shared_with_the_user(): void
    {
        $project = $this->projectWithBudgetData();
        $member = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => true, 'access_budget' => true]);
        $sharedContract = Contract::factory()->create(['project_id' => $project->id, 'name' => 'Shared contract']);
        $sharedContract->accessingUsers()->attach($member->id);
        $sharedFile = ProjectFile::factory()->create(['project_id' => $project->id, 'name' => 'shared.pdf']);
        $sharedFile->accessingUsers()->attach($member->id);
        $this->actingAs($member);

        $info = $this->getJson(route('projects.tabs.budget-informations', $project))
            ->assertOk()
            ->json('BudgetInformation');

        $this->assertSame(['shared.pdf'], collect($info['project_files'])->pluck('name')->all());
        $this->assertSame(['Shared contract'], collect($info['contracts'])->pluck('name')->all());
        $this->assertCount(1, $info['project_money_sources']);
    }

    #[Test]
    public function admin_gets_all_budget_information(): void
    {
        $project = $this->projectWithBudgetData();
        $this->actingAsAdmin();

        $info = $this->getJson(route('projects.tabs.budget-informations', $project))
            ->assertOk()
            ->json('BudgetInformation');

        $this->assertCount(1, $info['project_files']);
        $this->assertCount(1, $info['contracts']);
        $this->assertCount(1, $info['project_money_sources']);
    }

    #[Test]
    public function money_source_permission_alone_shows_money_sources_only(): void
    {
        $project = $this->projectWithBudgetData();
        $member = $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $project->users()->attach($member->id, ['can_write' => true]);

        $info = $this->getJson(route('projects.tabs.budget-informations', $project))
            ->assertOk()
            ->json('BudgetInformation');

        $this->assertCount(1, $info['project_money_sources']);
        $this->assertSame([], $info['contracts']);
        $this->assertSame([], $info['project_files']);
    }

    #[Test]
    public function shared_budget_files_are_checked_without_queries_per_file(): void
    {
        $project = $this->projectWithBudgetData();
        $member = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => true, 'access_budget' => true]);
        $this->actingAs($member);

        $queryCountFor = function (int $sharedFileCount) use ($project, $member): int {
            for ($index = 0; $index < $sharedFileCount; $index++) {
                ProjectFile::factory()
                    ->create(['project_id' => $project->id, 'is_budget_document' => true])
                    ->accessingUsers()
                    ->attach($member->id);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            $files = $this->getJson(route('projects.tabs.budget-informations', $project))
                ->assertOk()
                ->json('BudgetInformation.project_files');
            DB::disableQueryLog();

            // Das geteilte Projekt wird nicht je Datei mitgeschickt
            $this->assertArrayNotHasKey('project', $files[0]);

            return count(DB::getQueryLog());
        };

        // Aufwärmen: Rechte-Cache und Ähnliches sollen die Zählung nicht verfälschen
        $this->getJson(route('projects.tabs.budget-informations', $project))->assertOk();

        $queriesWithOneFile = $queryCountFor(1);
        $queriesWithSixFiles = $queryCountFor(5);

        $this->assertSame($queriesWithOneFile, $queriesWithSixFiles);
    }

    /**
     * BudgetInformations.vue, ProjectFileEditModal und ContractEditModal lesen die Freigabelisten unter
     * accessing_users / accessing_departments (resources/js/Helper/sharedAccess.js). Fehlen sie, startet das
     * Bearbeiten-Modal leer und das Speichern entzieht allen anderen Freigegebenen den Zugriff.
     */
    #[Test]
    public function budget_files_and_contracts_carry_their_share_lists(): void
    {
        $project = $this->projectWithBudgetData();
        $member = User::factory()->create();
        $colleague = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => true, 'access_budget' => true]);
        $department = Department::factory()->create();

        $file = ProjectFile::factory()->create([
            'project_id' => $project->id,
            'name' => 'shared-budget.pdf',
            'is_budget_document' => true,
        ]);
        $file->accessingUsers()->attach([$member->id, $colleague->id]);
        // Erstellerin: ContractPolicy lässt sie ohne Blick auf die Freigabeliste durch
        $contract = Contract::factory()->create([
            'project_id' => $project->id,
            'name' => 'Own contract',
            'creator_id' => $member->id,
        ]);
        $contract->accessingUsers()->attach($colleague->id);
        $contract->accessingDepartments()->attach($department->id);

        $sharedIds = fn (array $item, string $key): array => collect($item[$key] ?? null)
            ->pluck('id')->sort()->values()->all();
        $expectedFileUsers = collect([$member->id, $colleague->id])->sort()->values()->all();

        // freigegebene Person und Admin (Gate::before – die Policy lädt dann keine Relationen)
        foreach ([$member, $this->adminUser()] as $viewer) {
            $this->actingAs($viewer);
            $info = $this->getJson(route('projects.tabs.budget-informations', $project))
                ->assertOk()
                ->json('BudgetInformation');

            $sharedFile = collect($info['project_files'])->firstWhere('name', 'shared-budget.pdf');
            $this->assertSame($expectedFileUsers, $sharedIds($sharedFile, 'accessing_users'));

            $ownContract = collect($info['contracts'])->firstWhere('name', 'Own contract');
            $this->assertSame([$colleague->id], $sharedIds($ownContract, 'accessing_users'));
            $this->assertSame([$department->id], $sharedIds($ownContract, 'accessing_departments'));

            // Personen mit Budgetzugriff: die Upload-Modals filtern ihre Personensuche nach diesen Ids
            $this->assertContains($member->id, collect($info['access_budget'])->pluck('id')->all());

            // schlank: nur, was Liste und Modals lesen – keine Arbeitszeitkonten, Login-Kennungen, Pivot
            foreach ([
                $sharedFile['accessing_users'][0],
                $ownContract['accessing_users'][0],
                collect($info['access_budget'])->firstWhere('id', $member->id),
            ] as $sharedUser) {
                $this->assertEqualsCanonicalizing(
                    ['id', 'first_name', 'last_name', 'profile_photo_url'],
                    array_keys($sharedUser)
                );
            }
        }
    }

    #[Test]
    public function visible_contracts_are_checked_without_queries_per_contract(): void
    {
        $project = $this->projectWithBudgetData();
        $member = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => true, 'access_budget' => true]);
        $this->actingAs($member);

        $queryCountFor = function (int $contractCount) use ($project, $member): int {
            for ($index = 0; $index < $contractCount; $index++) {
                Contract::factory()
                    ->create(['project_id' => $project->id])
                    ->accessingUsers()
                    ->attach($member->id);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            $contracts = $this->getJson(route('projects.tabs.budget-informations', $project))
                ->assertOk()
                ->json('BudgetInformation.contracts');
            DB::disableQueryLog();

            $this->assertArrayNotHasKey('project', $contracts[0]);
            $this->assertArrayHasKey('currency', $contracts[0]);

            return count(DB::getQueryLog());
        };

        $this->getJson(route('projects.tabs.budget-informations', $project))->assertOk();

        $this->assertSame($queryCountFor(1), $queryCountFor(5));
    }

    private function projectWithBudgetData(): Project
    {
        $project = Project::factory()->create();
        ProjectFile::factory()->create(['project_id' => $project->id, 'name' => 'budget.pdf']);
        Contract::factory()->create(['project_id' => $project->id, 'name' => 'Secret contract']);
        $project->moneySources()->attach(MoneySource::factory()->create()->id);

        // Der Endpunkt setzt eine sichtbare Platzierung der Budgetinformationen voraus
        ComponentInTab::create([
            'project_tab_id' => ProjectTab::factory()->create(['visible_for_all' => true])->id,
            'component_id' => Component::create([
                'name' => 'Budget informations ' . uniqid(),
                'type' => ProjectTabComponentEnum::BUDGET_INFORMATIONS->value,
                'data' => [],
                'special' => true,
            ])->id,
            'order' => 1,
        ]);

        return $project;
    }
}
