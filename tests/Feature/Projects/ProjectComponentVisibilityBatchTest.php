<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Sammelprüfung visibleInProjectComponentIds(): gleiche Regel wie canSeeInProject() je Komponente,
 * aber mit fester Abfragezahl; der Service ist je Request geteilt (Tab-Cache greift über Policies hinweg).
 */
final class ProjectComponentVisibilityBatchTest extends FeatureTestCase
{
    private ProjectTab $visibleTab;

    private ProjectTab $hiddenTab;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $this->hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $this->viewer = User::factory()->create();
    }

    #[Test]
    public function batch_result_matches_the_single_component_rule(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->viewer->id);

        $unplaced = $this->createComponent('unplaced');
        $direct = $this->createComponent('direct visible');
        $this->placeInTab($this->visibleTab, $direct);
        $onlyHidden = $this->createComponent('only hidden tab');
        $this->placeInTab($this->hiddenTab, $onlyHidden);
        $hiddenAndVisible = $this->createComponent('hidden and visible tab');
        $this->placeInTab($this->hiddenTab, $hiddenAndVisible);
        $this->placeInTab($this->visibleTab, $hiddenAndVisible);

        $sidebarVisible = $this->createComponent('sidebar visible');
        $this->placeInSidebar($this->visibleTab, $sidebarVisible);
        $sidebarHidden = $this->createComponent('sidebar hidden');
        $this->placeInSidebar($this->hiddenTab, $sidebarHidden);

        $openFolder = $this->createComponent('open folder', type: 'DisclosureComponent');
        $this->placeInTab($this->visibleTab, $openFolder);
        $inOpenFolder = $this->createComponent('in open folder');
        $this->placeInFolder($openFolder, $inOpenFolder);
        $restrictedFolder = $this->createComponent('restricted folder', 'someSeeSomeEdit', 'DisclosureComponent');
        $this->placeInTab($this->visibleTab, $restrictedFolder);
        $inRestrictedFolder = $this->createComponent('in restricted folder');
        $this->placeInFolder($restrictedFolder, $inRestrictedFolder);

        $restricted = $this->createComponent('restricted', 'someSeeSomeEdit');
        $this->placeInTab($this->visibleTab, $restricted);
        $restrictedForUser = $this->createComponent('restricted for user', 'someSeeSomeEdit');
        $restrictedForUser->users()->attach($this->viewer->id);
        $this->placeInTab($this->visibleTab, $restrictedForUser);
        $restrictedForDepartment = $this->createComponent('restricted for department', 'someSeeSomeEdit');
        $restrictedForDepartment->departments()->attach($department->id);

        $expectedVisible = [
            $unplaced, $direct, $hiddenAndVisible, $sidebarVisible, $openFolder, $inOpenFolder,
            $restrictedForUser, $restrictedForDepartment,
        ];
        $all = [
            ...$expectedVisible, $onlyHidden, $sidebarHidden, $restrictedFolder, $inRestrictedFolder, $restricted,
        ];

        $service = $this->freshService();
        $batch = $service->visibleInProjectComponentIds($this->viewer, $all)->sort()->values()->all();

        $this->assertSame(collect($expectedVisible)->pluck('id')->sort()->values()->all(), $batch);
        foreach ($all as $component) {
            $this->assertSame(
                in_array($component->id, $batch, true),
                $this->freshService()->canSeeInProject($this->viewer, Component::query()->find($component->id)),
                'Abweichung bei ' . $component->name
            );
        }
        $this->assertCount(count($all), $this->freshService()->visibleInProjectComponentIds($this->adminUser(), $all));
    }

    #[Test]
    public function batch_query_count_does_not_grow_with_components(): void
    {
        $queriesFor = function (int $count): int {
            $components = collect(range(1, $count))->map(function (int $i) {
                $component = $this->createComponent('restricted ' . $i, 'someSeeSomeEdit');
                $component->users()->attach($this->viewer->id);
                $this->placeInTab($i % 2 ? $this->visibleTab : $this->hiddenTab, $component);
                $this->placeInSidebar($this->visibleTab, $component);

                return Component::query()->find($component->id);
            });
            // Tab- und Rechte-Cache vorwärmen: gemessen werden nur die Platzierungs-Abfragen
            $service = $this->freshService();
            $service->visibleTabIds($this->viewer);
            $service->bypassesComponentPermissions($this->viewer);

            DB::flushQueryLog();
            DB::enableQueryLog();
            $service->visibleInProjectComponentIds($this->viewer, $components);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $this->assertSame($queriesFor(2), $queriesFor(12));
    }

    #[Test]
    public function the_service_is_shared_within_a_request(): void
    {
        $this->assertSame(
            app(ProjectComponentVisibilityService::class),
            app(ProjectComponentVisibilityService::class)
        );

        $instance = app(ProjectComponentVisibilityService::class);
        app()->forgetScopedInstances();
        $this->assertNotSame($instance, app(ProjectComponentVisibilityService::class));
    }

    private function freshService(): ProjectComponentVisibilityService
    {
        app()->forgetScopedInstances();

        return app(ProjectComponentVisibilityService::class);
    }

    private function createComponent(
        string $name,
        ?string $permissionType = null,
        string $type = 'TextField'
    ): Component {
        return Component::create([
            'name' => $name,
            'type' => $type,
            'data' => [],
            'permission_type' => $permissionType,
        ]);
    }

    private function placeInTab(ProjectTab $tab, Component $component): void
    {
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);
    }

    private function placeInSidebar(ProjectTab $tab, Component $component): void
    {
        $sidebar = ProjectTabSidebarTab::query()->firstOrCreate(
            ['project_tab_id' => $tab->id],
            ['name' => 'Info', 'order' => 1]
        );
        $sidebar->componentsInSidebar()->create(['component_id' => $component->id, 'order' => 1]);
    }

    private function placeInFolder(Component $folder, Component $component): void
    {
        $nextId = max(
            (int) ComponentInTab::query()->max('id'),
            (int) DisclosureComponents::query()->max('id')
        ) + 1000;

        DisclosureComponents::query()->forceCreate([
            'id' => $nextId,
            'disclosure_id' => $folder->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);
    }
}
