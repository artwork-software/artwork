<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalProjectTabController;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TabShowTest extends TestCase
{
    private function makeComponent(string $type, string $name, array $data = []): Component
    {
        return Component::create([
            'name' => $name,
            'type' => $type,
            'data' => $data,
        ]);
    }

    private function attach(ProjectTab $tab, Component $component, int $order): ComponentInTab
    {
        return ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => $order,
        ]);
    }

    /**
     * Invokes the controller with the scope already attached (as the middleware
     * would) and returns the Inertia props.
     */
    private function renderProps(Project $project, ProjectTab $tab): array
    {
        $external = ExternalAccess::factory()->active()->create();
        $scope = ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $controller = $this->app->make(ExternalProjectTabController::class);
        $request = Request::create("/external/projects/{$project->id}/tabs/{$tab->id}", 'GET');
        $request->attributes->set('external_scope', $scope);

        $response = $controller->show($request, $project, $tab);

        return $response->toResponse($request)->getOriginalContent()->getData()['page']['props'];
    }

    #[Test]
    public function tab_show_returns_components_in_order(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $third = $this->makeComponent('TextField', 'Third');
        $first = $this->makeComponent('TextField', 'First');
        $second = $this->makeComponent('TextField', 'Second');

        $this->attach($tab, $third, 2);
        $this->attach($tab, $first, 0);
        $this->attach($tab, $second, 1);

        $props = $this->renderProps($project, $tab);

        $names = array_column($props['components'], 'name');
        $this->assertSame(['First', 'Second', 'Third'], $names);
    }

    #[Test]
    public function tab_show_filters_non_readable_components(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $this->attach($tab, $this->makeComponent('TextField', 'Readable'), 0);
        $this->attach($tab, $this->makeComponent('BudgetTab', 'Budget'), 1);
        $this->attach($tab, $this->makeComponent('ProjectTeamComponent', 'Team'), 2);

        $props = $this->renderProps($project, $tab);

        $names = array_column($props['components'], 'name');
        $this->assertSame(['Readable'], $names);
    }

    #[Test]
    public function tab_show_ignores_internal_visibility_restrictions(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $user = User::factory()->create();

        $component = $this->makeComponent('TextField', 'Restricted');
        // Internal visibility restriction: only this user may see/edit it.
        $component->users()->attach($user->id, ['can_write' => false]);
        $this->attach($tab, $component, 0);

        $props = $this->renderProps($project, $tab);

        $names = array_column($props['components'], 'name');
        $this->assertContains('Restricted', $names);
    }

    #[Test]
    public function tab_show_includes_disclosure_children(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $disclosure = $this->makeComponent('DisclosureComponent', 'Section');
        $cit = $this->attach($tab, $disclosure, 0);

        $childReadable = $this->makeComponent('TextField', 'Visible child');
        $childHidden = $this->makeComponent('BudgetTab', 'Hidden child');

        DisclosureComponents::create([
            'disclosure_id' => $disclosure->id,
            'component_id' => $childReadable->id,
            'order' => 0,
        ]);
        DisclosureComponents::create([
            'disclosure_id' => $disclosure->id,
            'component_id' => $childHidden->id,
            'order' => 1,
        ]);

        // give the readable child a project-specific value
        ProjectComponentValue::create([
            'project_id' => $project->id,
            'component_id' => $childReadable->id,
            'data' => ['text' => 'child value'],
        ]);

        $props = $this->renderProps($project, $tab);

        $this->assertCount(1, $props['components']);
        $children = $props['components'][0]['children'];
        $childNames = array_column($children, 'name');

        $this->assertSame(['Visible child'], $childNames);
        $this->assertSame(['text' => 'child value'], $children[0]['value']);
    }

    #[Test]
    public function tab_show_returns_value_from_project_component_value(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $component = $this->makeComponent('TextField', 'Field', ['text' => 'schema-default']);
        $this->attach($tab, $component, 0);
        ProjectComponentValue::create([
            'project_id' => $project->id,
            'component_id' => $component->id,
            'data' => ['text' => 'project-value'],
        ]);

        $props = $this->renderProps($project, $tab);

        $this->assertSame(['text' => 'project-value'], $props['components'][0]['value']);
        $this->assertTrue($props['components'][0]['is_writable']);
    }

    #[Test]
    public function tab_show_returns_404_for_unknown_project_or_tab(): void
    {
        // Route model binding runs (SubstituteBindings) before the auth
        // middleware in the external group, so unknown ids 404 regardless of auth.
        $response = $this->get('/external/projects/999999/tabs/999999');

        $response->assertNotFound();
    }
}
