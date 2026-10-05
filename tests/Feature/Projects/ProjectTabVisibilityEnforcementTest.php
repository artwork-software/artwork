<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Serverseitige Durchsetzung von Tab-Sichtbarkeit und Komponenten-Berechtigungen
 * (someSeeSomeEdit) für Tab-Payload, Daten-Endpunkte, Dateien und Drucklayouts.
 */
final class ProjectTabVisibilityEnforcementTest extends FeatureTestCase
{
    private const SECRET_VALUE = 'TOP-SECRET-COMPONENT-VALUE';

    private Project $project;

    private ProjectTab $visibleTab;

    private ProjectTab $hiddenTab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $this->hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
    }

    #[Test]
    public function tab_payload_omits_restricted_components_and_their_values(): void
    {
        $public = $this->createTextField('Public field');
        $this->setValue($public, 'public value');
        $secret = $this->createTextField('Secret field', 'someSeeSomeEdit');
        $this->setValue($secret, self::SECRET_VALUE);
        $secretInFolder = $this->createTextField('Secret in folder', 'someSeeSomeEdit');
        $this->setValue($secretInFolder, self::SECRET_VALUE . '-folder');
        $secretInSidebar = $this->createTextField('Secret in sidebar', 'someSeeSomeEdit');
        $this->setValue($secretInSidebar, self::SECRET_VALUE . '-sidebar');

        $this->placeInTab($this->visibleTab, $public, 1);
        $this->placeInTab($this->visibleTab, $secret, 2);
        $folder = $this->createFolder();
        $this->placeInTab($this->visibleTab, $folder, 3);
        $this->placeInFolder($folder, $public, 1);
        $this->placeInFolder($folder, $secretInFolder, 2);
        $sidebar = ProjectTabSidebarTab::create([
            'project_tab_id' => $this->visibleTab->id,
            'name' => 'Info',
            'order' => 1,
        ]);
        $sidebar->componentsInSidebar()->create(['component_id' => $secretInSidebar->id, 'order' => 1]);

        $this->actingAs($this->teamMember());

        $response = $this->get($this->tabUrl($this->visibleTab))->assertOk();

        $currentTab = $response->viewData('page')['props']['currentTab'];
        $componentIds = collect($currentTab['components'])->pluck('component_id')->all();
        $this->assertContains($public->id, $componentIds);
        $this->assertNotContains($secret->id, $componentIds);

        $folderPlacement = collect($currentTab['components'])->firstWhere('component_id', $folder->id);
        $this->assertSame(
            [$public->id],
            collect($folderPlacement['disclosure_components'])->pluck('component_id')->all()
        );
        $this->assertSame([], $currentTab['sidebar_tabs'][0]['components_in_sidebar']);

        $response->assertSee('public value');
        $response->assertDontSee(self::SECRET_VALUE);
    }

    #[Test]
    public function component_users_and_write_all_projects_users_receive_restricted_components(): void
    {
        $secret = $this->createTextField('Secret field', 'someSeeSomeEdit');
        $this->setValue($secret, self::SECRET_VALUE);
        $this->placeInTab($this->visibleTab, $secret, 1);

        $listedUser = $this->teamMember();
        $secret->users()->attach($listedUser->id, ['can_write' => false]);
        $this->actingAs($listedUser);
        $this->get($this->tabUrl($this->visibleTab))->assertOk()->assertSee(self::SECRET_VALUE);

        $writeAll = $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $this->actingAs($writeAll);
        $this->get($this->tabUrl($this->visibleTab))->assertOk()->assertSee(self::SECRET_VALUE);
    }

    #[Test]
    public function documents_component_in_a_hidden_tab_is_forbidden(): void
    {
        $placement = $this->placeInTab(
            $this->hiddenTab,
            $this->createSpecial('ProjectDocumentsComponent'),
            1,
            [$this->hiddenTab->id]
        );
        $this->createFile($this->hiddenTab, 'hidden.pdf');

        $this->actingAs($this->teamMember());

        $this->getJson($this->documentsUrl($placement->id))->assertForbidden();
    }

    #[Test]
    public function restricted_documents_component_is_forbidden(): void
    {
        $placement = $this->placeInTab(
            $this->visibleTab,
            $this->createSpecial('ProjectDocumentsComponent', 'someSeeSomeEdit'),
            1,
            [$this->visibleTab->id]
        );

        $this->actingAs($this->teamMember());

        $this->getJson($this->documentsUrl($placement->id))->assertForbidden();
    }

    #[Test]
    public function documents_scope_drops_tabs_the_viewer_cannot_see(): void
    {
        $placement = $this->placeInTab(
            $this->visibleTab,
            $this->createSpecial('ProjectDocumentsComponent'),
            1,
            [$this->visibleTab->id, $this->hiddenTab->id]
        );
        $this->createFile($this->visibleTab, 'visible.pdf');
        $this->createFile($this->hiddenTab, 'hidden.pdf');

        $this->actingAs($this->teamMember());

        $names = collect($this->getJson($this->documentsUrl($placement->id))->assertOk()->json('documents'))
            ->pluck('name')->all();
        $this->assertSame(['visible.pdf'], $names);

        $this->actingAsAdmin();
        $names = collect($this->getJson($this->documentsUrl($placement->id))->assertOk()->json('documents'))
            ->pluck('name')->sort()->values()->all();
        $this->assertSame(['hidden.pdf', 'visible.pdf'], $names);
    }

    #[Test]
    public function documents_in_a_folder_use_the_folder_placement_and_its_scope(): void
    {
        $otherTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $folder = $this->createFolder();
        $this->placeInTab($this->visibleTab, $folder, 1);
        $documents = $this->createSpecial('ProjectDocumentsComponent');
        $folderPlacement = $this->placeInFolder($folder, $documents, 1, [$this->visibleTab->id]);

        // Eine Tab-Platzierung mit derselben Id darf nicht stattdessen verwendet werden
        $colliding = ComponentInTab::query()->forceCreate([
            'id' => $folderPlacement->id,
            'project_tab_id' => $otherTab->id,
            'component_id' => $documents->id,
            'order' => 99,
            'scope' => [$otherTab->id],
        ]);
        $this->assertSame($folderPlacement->id, $colliding->id);

        $this->createFile($this->visibleTab, 'folder-scope.pdf');
        $this->createFile($otherTab, 'other-tab.pdf');

        $this->actingAs($this->teamMember());

        $names = collect(
            $this->getJson($this->documentsUrl($folderPlacement->id, 'disclosure'))->assertOk()->json('documents')
        )->pluck('name')->all();

        $this->assertSame(['folder-scope.pdf'], $names);
    }

    #[Test]
    public function comments_in_a_folder_use_the_folder_scope_instead_of_all_comments(): void
    {
        $folder = $this->createFolder();
        $this->placeInTab($this->visibleTab, $folder, 1);
        $folderPlacement = $this->placeInFolder($folder, $this->createSpecial('CommentTab'), 1, [$this->visibleTab->id]);
        $otherTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $this->createComment($this->visibleTab, 'in scope');
        $this->createComment($otherTab, 'out of scope');

        $this->actingAs($this->teamMember());

        $texts = collect(
            $this->getJson(route('projects.tabs.comments', [
                'project' => $this->project->id,
                'componentInTab' => $folderPlacement->id,
                'placement' => 'disclosure',
            ]))->assertOk()->json('comments')
        )->pluck('text')->all();

        $this->assertSame(['in scope'], $texts);
    }

    #[Test]
    public function folder_component_in_a_hidden_tab_is_forbidden(): void
    {
        $folder = $this->createFolder();
        $this->placeInTab($this->hiddenTab, $folder, 1);
        $folderPlacement = $this->placeInFolder($folder, $this->createSpecial('ChecklistComponent'), 1, [$this->hiddenTab->id]);

        $this->actingAs($this->teamMember());

        $this->getJson(route('projects.tabs.checklists', [
            'project' => $this->project->id,
            'componentInTab' => $folderPlacement->id,
            'placement' => 'disclosure',
        ]))->assertForbidden();
    }

    #[Test]
    public function all_comments_and_all_checklists_omit_hidden_tabs(): void
    {
        $this->createComment($this->visibleTab, 'visible comment');
        $this->createComment($this->hiddenTab, 'hidden comment');
        $this->createComment(null, 'untabbed comment');
        $this->createChecklist($this->visibleTab, 'visible list');
        $this->createChecklist($this->hiddenTab, 'hidden list');

        $this->actingAs($this->teamMember());

        $texts = collect(
            $this->getJson(route('projects.tabs.all-comments', $this->project))->assertOk()->json('comments')
        )->pluck('text')->sort()->values()->all();
        $this->assertSame(['untabbed comment', 'visible comment'], $texts);

        $names = collect(
            $this->getJson(route('projects.tabs.all-checklists', $this->project))
                ->assertOk()
                ->json('public_all_checklists')
        )->pluck('name')->all();
        $this->assertSame(['visible list'], $names);
    }

    #[Test]
    public function project_files_of_hidden_tabs_cannot_be_downloaded(): void
    {
        $hiddenFile = $this->createFile($this->hiddenTab, 'hidden.pdf');
        $visibleFile = $this->createFile($this->visibleTab, 'visible.pdf');
        $sharedHiddenFile = $this->createFile($this->hiddenTab, 'shared.pdf');
        $member = $this->teamMember();
        $sharedHiddenFile->accessingUsers()->attach($member->id);

        $this->actingAs($member);

        $this->get(route('download_file', $hiddenFile))->assertForbidden();
        $this->get(route('download_file', $visibleFile))->assertOk();
        $this->get(route('download_file', $sharedHiddenFile))->assertOk();

        $this->actingAsAdmin();
        $this->get(route('download_file', $hiddenFile))->assertOk();
    }

    #[Test]
    public function files_cannot_be_uploaded_into_a_hidden_tab(): void
    {
        $this->actingAs($this->teamMember());

        $this->post(route('project_files.store', $this->project), [
            'file' => UploadedFile::fake()->create('upload.pdf', 10, 'application/pdf'),
            'tabId' => $this->hiddenTab->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('project_files', [
            'project_id' => $this->project->id,
            'tab_id' => $this->hiddenTab->id,
        ]);
    }

    #[Test]
    public function inactive_print_layouts_cannot_be_printed_by_project_members(): void
    {
        $layout = $this->createLayout(['is_active' => false]);

        $this->actingAs($this->teamMember());
        $this->get($this->printUrl($layout))->assertNotFound();

        $this->actingAsAdmin();
        $this->get($this->printUrl($layout))->assertOk();
    }

    #[Test]
    public function print_layout_omits_components_the_viewer_cannot_see(): void
    {
        $public = $this->createTextField('Public field');
        $this->setValue($public, 'printed public value');
        $this->placeInTab($this->visibleTab, $public, 1);

        $restricted = $this->createTextField('Restricted field', 'someSeeSomeEdit');
        $this->setValue($restricted, self::SECRET_VALUE);
        $this->placeInTab($this->visibleTab, $restricted, 2);

        $onlyInHiddenTab = $this->createTextField('Hidden tab field');
        $this->setValue($onlyInHiddenTab, self::SECRET_VALUE . '-hidden-tab');
        $this->placeInTab($this->hiddenTab, $onlyInHiddenTab, 1);

        $layout = $this->createLayout();
        $this->placeInLayout($layout, $public, 1);
        $this->placeInLayout($layout, $restricted, 2);
        $this->placeInLayout($layout, $onlyInHiddenTab, 3);

        $this->actingAs($this->teamMember());

        $response = $this->get($this->printUrl($layout))->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertSame(
            [$public->id],
            collect($props['layout']['body_components'])->pluck('component_id')->all()
        );
        $response->assertSee('printed public value');
        $response->assertDontSee(self::SECRET_VALUE);

        $this->actingAsAdmin();
        $this->get($this->printUrl($layout))->assertOk()
            ->assertSee(self::SECRET_VALUE)
            ->assertSee(self::SECRET_VALUE . '-hidden-tab');
    }

    #[Test]
    public function print_layout_lists_only_documents_and_comments_of_visible_tabs(): void
    {
        $this->createFile($this->visibleTab, 'visible.pdf');
        $this->createFile($this->hiddenTab, 'hidden-file.pdf');
        $this->createComment($this->visibleTab, 'visible comment');
        $this->createComment($this->hiddenTab, 'hidden comment text');

        $layout = $this->createLayout();
        $this->placeInLayout($layout, $this->createSpecial('ProjectAllDocumentsComponent'), 1);
        $this->placeInLayout($layout, $this->createSpecial('CommentAllTab'), 2);

        $this->actingAs($this->teamMember());

        $this->get($this->printUrl($layout))->assertOk()
            ->assertSee('visible.pdf')
            ->assertSee('visible comment')
            ->assertDontSee('hidden-file.pdf')
            ->assertDontSee('hidden comment text');
    }

    private function teamMember(): User
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user->id, ['can_write' => true]);

        return $user;
    }

    private function createTextField(string $label, ?string $permissionType = null): Component
    {
        return Component::create([
            'name' => $label,
            'type' => 'TextField',
            'data' => ['label' => $label, 'text' => '', 'placeholder' => ''],
            'permission_type' => $permissionType,
        ]);
    }

    private function createSpecial(string $type, ?string $permissionType = null): Component
    {
        return Component::create([
            'name' => $type . ' ' . uniqid(),
            'type' => $type,
            'data' => [],
            'special' => true,
            'permission_type' => $permissionType,
        ]);
    }

    private function createFolder(): Component
    {
        return Component::create([
            'name' => 'Folder ' . uniqid(),
            'type' => 'DisclosureComponent',
            'data' => ['label' => 'Folder'],
        ]);
    }

    private function setValue(Component $component, string $text): void
    {
        ProjectComponentValue::create([
            'component_id' => $component->id,
            'project_id' => $this->project->id,
            'data' => ['text' => $text],
        ]);
    }

    /**
     * @param array<int, int>|null $scope
     */
    private function placeInTab(ProjectTab $tab, Component $component, int $order, ?array $scope = null): ComponentInTab
    {
        return ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => $order,
            'scope' => $scope,
        ]);
    }

    /**
     * @param array<int, int>|null $scope
     */
    private function placeInFolder(
        Component $folder,
        Component $component,
        int $order,
        ?array $scope = null
    ): DisclosureComponents {
        $nextId = max(
            (int) ComponentInTab::query()->max('id'),
            (int) DisclosureComponents::query()->max('id')
        ) + 1000;

        return DisclosureComponents::query()->forceCreate([
            'id' => $nextId,
            'disclosure_id' => $folder->id,
            'component_id' => $component->id,
            'order' => $order,
            'scope' => $scope,
        ]);
    }

    private function createFile(?ProjectTab $tab, string $name): ProjectFile
    {
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $this->project->id,
            'tab_id' => $tab?->id,
            'name' => $name,
            'basename' => uniqid() . $name,
        ]);
        Storage::put('project_files/' . $file->basename, 'content');

        return $file;
    }

    private function createComment(?ProjectTab $tab, string $text): Comment
    {
        return Comment::create([
            'text' => $text,
            'project_id' => $this->project->id,
            'user_id' => User::factory()->create()->id,
            'tab_id' => $tab?->id,
        ]);
    }

    private function createChecklist(ProjectTab $tab, string $name): Checklist
    {
        return Checklist::create([
            'name' => $name,
            'project_id' => $this->project->id,
            'user_id' => User::factory()->create()->id,
            'tab_id' => $tab->id,
            'private' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createLayout(array $attributes = []): ProjectPrintLayout
    {
        return ProjectPrintLayout::create(array_merge([
            'name' => 'Visibility layout',
            'description' => 'Test',
            'columns_header' => 1,
            'columns_body' => 1,
            'columns_footer' => 1,
            'order' => 1,
            'is_active' => true,
            'user_id' => User::factory()->create()->id,
            'notes' => ['header' => [], 'footer' => []],
        ], $attributes));
    }

    private function placeInLayout(ProjectPrintLayout $layout, Component $component, int $row): void
    {
        PrintLayoutComponents::create([
            'project_print_layout_id' => $layout->id,
            'component_id' => $component->id,
            'type' => 'body',
            'row' => $row,
            'position' => 1,
        ]);
    }

    private function tabUrl(ProjectTab $tab): string
    {
        return route('projects.tab', ['project' => $this->project->id, 'projectTab' => $tab->id]);
    }

    private function documentsUrl(int $placementId, ?string $placement = null): string
    {
        return route('projects.tabs.documents', array_filter([
            'project' => $this->project->id,
            'componentInTab' => $placementId,
            'placement' => $placement,
        ]));
    }

    private function printUrl(ProjectPrintLayout $layout): string
    {
        return route('project-print-layout.show', [
            'project' => $this->project->id,
            'projectPrintLayout' => $layout->id,
        ]);
    }
}
