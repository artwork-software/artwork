<?php

namespace Tests\Feature\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalScopeResolver;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalScopeResolverTest extends TestCase
{
    private function resolver(): ExternalScopeResolver
    {
        return $this->app->make(ExternalScopeResolver::class);
    }

    /**
     * @return array{external: ExternalAccess, project: Project, tab: ProjectTab}
     */
    private function context(): array
    {
        return [
            'external' => ExternalAccess::factory()->active()->create(),
            'project' => Project::factory()->create(),
            'tab' => ProjectTab::factory()->create(),
        ];
    }

    #[Test]
    public function resolve_scope_returns_null_when_no_scope_exists(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        $this->assertNull($this->resolver()->resolveScope($external, $project->id, $tab->id));
    }

    #[Test]
    public function resolve_scope_returns_null_when_scope_is_expired(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        ExternalAccessScope::factory()->expired()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $this->assertNull($this->resolver()->resolveScope($external, $project->id, $tab->id));
    }

    #[Test]
    public function resolve_scope_returns_null_when_scope_is_not_yet_valid(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        ExternalAccessScope::factory()->notYetValid()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $this->assertNull($this->resolver()->resolveScope($external, $project->id, $tab->id));
    }

    #[Test]
    public function resolve_scope_returns_scope_when_currently_valid(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        $scope = ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $resolved = $this->resolver()->resolveScope($external, $project->id, $tab->id);

        $this->assertNotNull($resolved);
        $this->assertSame($scope->id, $resolved->id);
    }

    #[Test]
    public function can_write_returns_true_for_write_scope(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        ExternalAccessScope::factory()->write()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $this->assertTrue($this->resolver()->canWrite($external, $project->id, $tab->id));
    }

    #[Test]
    public function can_write_returns_false_for_read_scope(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $this->assertFalse($this->resolver()->canWrite($external, $project->id, $tab->id));
    }

    #[Test]
    public function component_belongs_to_tab_returns_true_for_component_in_tab(): void
    {
        $tab = ProjectTab::factory()->create();
        $component = Component::create(['name' => 'Field', 'type' => 'TextField', 'data' => []]);
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 0,
        ]);

        $this->assertTrue($this->resolver()->componentBelongsToTab($component->id, $tab->id));
    }

    #[Test]
    public function component_belongs_to_tab_returns_false_for_component_in_different_tab(): void
    {
        $tabA = ProjectTab::factory()->create();
        $tabB = ProjectTab::factory()->create();
        $component = Component::create(['name' => 'Field', 'type' => 'TextField', 'data' => []]);
        ComponentInTab::create([
            'project_tab_id' => $tabA->id,
            'component_id' => $component->id,
            'order' => 0,
        ]);

        $this->assertFalse($this->resolver()->componentBelongsToTab($component->id, $tabB->id));
    }
}
