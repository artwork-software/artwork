<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
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

    private function projectWithBudgetData(): Project
    {
        $project = Project::factory()->create();
        ProjectFile::factory()->create(['project_id' => $project->id, 'name' => 'budget.pdf']);
        Contract::factory()->create(['project_id' => $project->id, 'name' => 'Secret contract']);
        $project->moneySources()->attach(MoneySource::factory()->create()->id);

        return $project;
    }
}
