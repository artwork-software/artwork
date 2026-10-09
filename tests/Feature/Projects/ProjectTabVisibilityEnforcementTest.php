<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Events\DeleteDocumentInProject;
use Artwork\Modules\Project\Events\UploadNewDocumentInProject;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
    public function only_listed_users_and_admins_receive_restricted_components(): void
    {
        $secret = $this->createTextField('Secret field', 'someSeeSomeEdit');
        $this->setValue($secret, self::SECRET_VALUE);
        $this->placeInTab($this->visibleTab, $secret, 1);

        $listedUser = $this->teamMember();
        $secret->users()->attach($listedUser->id, ['can_write' => false]);
        $this->actingAs($listedUser);
        $this->get($this->tabUrl($this->visibleTab))->assertOk()->assertSee(self::SECRET_VALUE);

        // Globales "write projects" erweitert "Sehen dürfen nur die Folgenden" nicht (Produktentscheidung)
        $writeAll = $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $this->actingAs($writeAll);
        $this->get($this->tabUrl($this->visibleTab))->assertOk()->assertDontSee(self::SECRET_VALUE);

        $listedWriteAll = $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value, $this->teamMember());
        $secret->users()->attach($listedWriteAll->id, ['can_write' => false]);
        $this->actingAs($listedWriteAll);
        $this->get($this->tabUrl($this->visibleTab))->assertOk()->assertSee(self::SECRET_VALUE);

        $this->actingAsAdmin();
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
        $this->placeAllComponentsInVisibleTab();

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
    public function deleting_a_tab_visible_for_all_keeps_its_contents_visible_for_all(): void
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $comment = $this->createComment($tab, 'comment of deleted tab');
        $checklist = $this->createChecklist($tab, 'list of deleted tab');
        $file = $this->createFile($tab, 'deleted-tab.pdf');

        $this->actingAsAdmin();
        $this->delete(route('tab.destroy', $tab))->assertSuccessful();

        $this->assertNull($comment->fresh()->tab_id);
        $this->assertNull($checklist->fresh()->tab_id);
        $this->assertNull($file->fresh()->tab_id);

        $this->placeAllComponentsInVisibleTab();
        $this->actingAs($this->teamMember());
        $this->assertSame(
            ['comment of deleted tab'],
            collect($this->getJson(route('projects.tabs.all-comments', $this->project))->json('comments'))
                ->pluck('text')->all()
        );
        $this->get(route('download_file', $file))->assertOk();
    }

    #[Test]
    public function contents_of_a_deleted_restricted_tab_stay_hidden_except_for_admins(): void
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $tabId = $tab->id;
        $comment = $this->createComment($tab, 'secret comment');
        $this->createChecklist($tab, 'secret list');
        $file = $this->createFile($tab, 'secret.pdf');

        $this->actingAsAdmin();
        $this->delete(route('tab.destroy', $tab))->assertSuccessful();

        $this->assertSame($tabId, $comment->fresh()->tab_id);

        $this->placeAllComponentsInVisibleTab();
        $this->actingAs($this->teamMember());
        $this->assertSame([], $this->getJson(route('projects.tabs.all-comments', $this->project))->json('comments'));
        $this->assertSame(
            [],
            $this->getJson(route('projects.tabs.all-checklists', $this->project))->json('public_all_checklists')
        );
        $this->get(route('download_file', $file))->assertForbidden();

        $this->actingAsAdmin();
        $this->assertSame(
            ['secret comment'],
            collect($this->getJson(route('projects.tabs.all-comments', $this->project))->json('comments'))
                ->pluck('text')->all()
        );
        $this->assertSame(
            ['secret list'],
            collect($this->getJson(route('projects.tabs.all-checklists', $this->project))->json('public_all_checklists'))
                ->pluck('name')->all()
        );
    }

    #[Test]
    public function migration_frees_comments_and_checklists_of_previously_deleted_tabs(): void
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $orphanComment = $this->createComment($tab, 'orphan comment');
        $orphanChecklist = $this->createChecklist($tab, 'orphan list');
        $orphanFile = $this->createFile($tab, 'orphan.pdf');
        $keptComment = $this->createComment($this->hiddenTab, 'hidden comment');
        $orphanTabId = $tab->id;
        ProjectTab::query()->whereKey($orphanTabId)->delete();

        $migration = require database_path(
            'migrations/2026_10_06_122333_null_orphaned_tab_ids_of_comments_and_checklists.php'
        );
        $migration->up();
        $migration->up();

        $this->assertNull($orphanComment->fresh()->tab_id);
        $this->assertNull($orphanChecklist->fresh()->tab_id);
        $this->assertSame($orphanTabId, $orphanFile->fresh()->tab_id);
        $this->assertSame($this->hiddenTab->id, $keptComment->fresh()->tab_id);
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

        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $this->get($this->printUrl($layout))->assertOk()
            ->assertSee('printed public value')
            ->assertDontSee(self::SECRET_VALUE);

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

    #[Test]
    public function print_layout_shows_checklists_of_deleted_restricted_tabs_only_to_admins(): void
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $orphan = $this->createChecklist($tab, 'orphan list');
        $visible = $this->createChecklist($this->visibleTab, 'visible list');
        ProjectTab::query()->whereKey($tab->id)->delete();

        $layout = $this->createLayout();
        $this->placeInLayout($layout, $this->createSpecial(ProjectTabComponentEnum::CHECKLIST_ALL->value), 1);
        $printedChecklistIds = fn (): array => collect(data_get(
            $this->get($this->printUrl($layout))->assertOk()->viewData('page')['props']['project'],
            'opened_checklists'
        ))->sort()->values()->all();

        $this->actingAs($this->teamMember());
        $this->assertSame([$visible->id], $printedChecklistIds());

        $this->actingAsAdmin();
        $this->assertSame(collect([$orphan->id, $visible->id])->sort()->values()->all(), $printedChecklistIds());
    }

    #[Test]
    public function all_endpoints_require_a_visible_all_component(): void
    {
        $member = $this->teamMember();
        $this->actingAs($member);
        $allRoutes = ['projects.tabs.all-comments', 'projects.tabs.all-checklists', 'projects.tabs.all-documents'];
        $allTypes = [
            ProjectTabComponentEnum::COMMENT_ALL_TAB,
            ProjectTabComponentEnum::CHECKLIST_ALL,
            ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS,
        ];

        // nicht platziert
        foreach ($allRoutes as $routeName) {
            $this->getJson(route($routeName, $this->project))->assertForbidden();
        }

        // nur in einem verborgenen Tab bzw. als "Sehen dürfen nur die Folgenden" ohne die Person
        $restricted = [];
        foreach ($allTypes as $order => $type) {
            $this->placeInTab($this->hiddenTab, $this->createSpecial($type->value), $order + 1);
            $restricted[] = $component = $this->createSpecial($type->value, 'someSeeSomeEdit');
            $this->placeInTab($this->visibleTab, $component, $order + 1);
        }
        foreach ($allRoutes as $routeName) {
            $this->getJson(route($routeName, $this->project))->assertForbidden();
        }

        // ausdrücklich für die Person freigegeben
        foreach ($restricted as $component) {
            $component->users()->attach($member->id, ['can_write' => false]);
        }
        foreach ($allRoutes as $routeName) {
            $this->getJson(route($routeName, $this->project))->assertOk();
        }

        // Admins sehen die Daten auch ohne Platzierung
        $this->actingAsAdmin();
        foreach ($allRoutes as $routeName) {
            $this->getJson(route($routeName, $this->project))->assertOk();
        }
    }

    #[Test]
    public function budget_documents_are_only_listed_and_served_like_in_the_budget_informations(): void
    {
        $this->placeAllComponentsInVisibleTab();
        $sharedBudgetUser = $this->teamMember(['access_budget' => true]);
        $sharedManager = $this->teamMember(['is_manager' => true]);
        $sharedBudgetAdmin = $this->teamMember();
        $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value, $sharedBudgetAdmin);
        $sharedWithoutBudgetRights = $this->teamMember();
        $unsharedManager = $this->teamMember(['is_manager' => true]);
        $unsharedBudgetUser = $this->teamMember(['access_budget' => true]);
        $otherMember = $this->teamMember();

        $budgetFile = $this->createFile(null, 'budget-shared.pdf', true);
        $budgetFile->accessingUsers()->attach([
            $sharedBudgetUser->id,
            $sharedManager->id,
            $sharedBudgetAdmin->id,
            $sharedWithoutBudgetRights->id,
        ]);
        // Ohne Kennzeichnung entscheidet die Freigabeliste nicht über die Sichtbarkeit
        $plainFile = $this->createFile(null, 'plain-untabbed.pdf');
        $plainFile->accessingUsers()->attach([$sharedBudgetUser->id, $sharedManager->id]);
        $legacyFile = $this->createFile(null, 'legacy-untabbed.pdf');

        $listedNames = fn (): array => collect(
            $this->getJson(route('projects.tabs.all-documents', $this->project))->assertOk()->json('documents')
        )->pluck('name')->sort()->values()->all();

        foreach ([$sharedWithoutBudgetRights, $unsharedManager, $unsharedBudgetUser, $otherMember] as $deniedUser) {
            $this->actingAs($deniedUser);
            $this->assertSame(['legacy-untabbed.pdf', 'plain-untabbed.pdf'], $listedNames());
            $this->get(route('download_file', $budgetFile))->assertForbidden();
            $this->get(route('download_file', $plainFile))->assertOk();
            $this->get(route('download_file', $legacyFile))->assertOk();
        }

        foreach ([$sharedBudgetUser, $sharedManager, $sharedBudgetAdmin] as $allowedUser) {
            $this->actingAs($allowedUser);
            $this->assertSame(
                ['budget-shared.pdf', 'legacy-untabbed.pdf', 'plain-untabbed.pdf'],
                $listedNames()
            );
            $this->get(route('download_file', $budgetFile))->assertOk();
        }

        $this->actingAsAdmin();
        $this->assertContains('budget-shared.pdf', $listedNames());
        $this->get(route('download_file', $budgetFile))->assertOk();
    }

    #[Test]
    public function print_layout_omits_budget_documents_for_other_members(): void
    {
        $sharedBudgetUser = $this->teamMember(['access_budget' => true]);
        $budgetFile = $this->createFile(null, 'budget-print.pdf', true);
        $budgetFile->accessingUsers()->attach($sharedBudgetUser->id);
        $this->createFile(null, 'plain-print.pdf');

        $layout = $this->createLayout();
        $this->placeInLayout($layout, $this->createSpecial('ProjectAllDocumentsComponent'), 1);

        $this->actingAs($this->teamMember());
        $this->get($this->printUrl($layout))->assertOk()
            ->assertSee('plain-print.pdf')
            ->assertDontSee('budget-print.pdf');

        $this->actingAs($sharedBudgetUser);
        $this->get($this->printUrl($layout))->assertOk()->assertSee('budget-print.pdf');
    }

    #[Test]
    public function only_uploads_from_the_budget_informations_are_flagged_as_budget_documents(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['pdf'];
        $settings->allowed_project_file_size = 10;
        $settings->save();
        $this->actingAsAdmin();

        $upload = fn (array $data) => $this->post(
            route('project_files.store', $this->project),
            array_merge(['file' => $this->pdfUpload()], $data),
            ['Accept' => 'application/json']
        )->assertSuccessful();

        // ProjectFileUploadModal (Budget-Informationen)
        $upload(['budgetDocument' => '1', 'accessibleUsers' => [User::factory()->create()->id]]);
        // Dokumente-Komponente in der Seitenleiste bzw. im Tab
        $upload([]);
        $upload(['budgetDocument' => '1', 'tabId' => $this->visibleTab->id]);

        $this->assertSame(
            [true, false, false],
            ProjectFile::query()
                ->where('project_id', $this->project->id)
                ->orderBy('id')
                ->get()
                ->map(fn (ProjectFile $projectFile): bool => $projectFile->is_budget_document)
                ->all()
        );
    }

    #[Test]
    public function updating_the_share_list_keeps_the_acting_person(): void
    {
        $budgetUser = $this->teamMember(['access_budget' => true]);
        $otherUser = User::factory()->create();
        $file = $this->createFile(null, 'budget-edit.pdf', true);
        $file->accessingUsers()->attach([$budgetUser->id, $otherUser->id]);

        $this->actingAs($budgetUser);
        $this->post(route('project_files.update', $file), ['accessibleUsers' => [$otherUser->id]])
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$budgetUser->id, $otherUser->id],
            $file->fresh()->accessingUsers->pluck('id')->all()
        );
        $this->get(route('download_file', $file))->assertOk();
    }

    #[Test]
    public function file_download_follows_project_view_rights(): void
    {
        $untabbedFile = $this->createFile(null, 'untabbed.pdf');
        $tabFile = $this->createFile($this->visibleTab, 'tabbed.pdf');

        // "write projects" ohne Teamzugehörigkeit sieht das Projekt und damit die Dateilisten
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $this->get(route('download_file', $untabbedFile))->assertOk();
        $this->get(route('download_file', $tabFile))->assertOk();

        $this->actingAs(User::factory()->create());
        $this->get(route('download_file', $untabbedFile))->assertForbidden();
        $this->get(route('download_file', $tabFile))->assertForbidden();
    }

    #[Test]
    public function document_broadcasts_carry_only_ids(): void
    {
        $file = $this->createFile($this->hiddenTab, 'secret-name.pdf');
        $expected = [
            'document' => [
                'id' => $file->id,
                'tab_id' => $this->hiddenTab->id,
                'project_id' => $this->project->id,
            ],
        ];

        $this->assertSame($expected, (new UploadNewDocumentInProject($file, $this->project->id))->broadcastWith());
        $this->assertSame($expected, (new DeleteDocumentInProject($file, $this->project->id))->broadcastWith());
    }

    #[Test]
    public function document_deletion_is_broadcast_after_the_file_is_deleted(): void
    {
        $file = $this->createFile($this->visibleTab, 'to-delete.pdf');
        $trashedWhenBroadcast = null;
        Event::listen(
            DeleteDocumentInProject::class,
            function (DeleteDocumentInProject $event) use (&$trashedWhenBroadcast): void {
                $trashedWhenBroadcast = ProjectFile::withTrashed()->find($event->projectFile->id)?->trashed();
            }
        );

        $this->actingAsAdmin();
        $this->delete(route('project_files.destroy', $file))->assertSuccessful();

        $this->assertTrue($trashedWhenBroadcast);
    }

    #[Test]
    public function an_unreachable_websocket_server_does_not_fail_saved_document_changes(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['pdf'];
        $settings->allowed_project_file_size = 10;
        $settings->save();
        // Reverb nicht erreichbar: der Broadcast wirft wie das Pusher-SDK bei einem Verbindungsfehler
        $failBroadcast = function (): void {
            throw new \RuntimeException('Pusher error: cURL error 7: Failed to connect to reverb port 8080.');
        };
        Event::listen(UploadNewDocumentInProject::class, $failBroadcast);
        Event::listen(DeleteDocumentInProject::class, $failBroadcast);
        $this->actingAsAdmin();

        $this->post(
            route('project_files.store', $this->project),
            ['file' => $this->pdfUpload()],
            ['Accept' => 'application/json']
        )->assertSuccessful();
        $file = ProjectFile::query()->where('project_id', $this->project->id)->latest('id')->firstOrFail();

        $this->delete(route('project_files.destroy', $file))->assertSuccessful();
        $this->assertSoftDeleted($file);
    }

    #[Test]
    public function document_broadcasts_wait_for_the_database_commit(): void
    {
        $file = $this->createFile($this->visibleTab, 'uploaded.pdf');
        $broadcastCount = 0;
        Event::listen(UploadNewDocumentInProject::class, function () use (&$broadcastCount): void {
            $broadcastCount++;
        });

        DB::transaction(function () use ($file, &$broadcastCount): void {
            event(new UploadNewDocumentInProject($file, $this->project->id));
            $this->assertSame(0, $broadcastCount);
        });

        $this->assertSame(1, $broadcastCount);
    }

    #[Test]
    public function migration_flags_untabbed_files_with_a_share_list_as_budget_documents(): void
    {
        $sharedFile = $this->createFile(null, 'shared.pdf');
        $sharedFile->accessingUsers()->attach([User::factory()->create()->id, User::factory()->create()->id]);
        $singleFile = $this->createFile(null, 'single.pdf');
        $singleFile->accessingUsers()->attach(User::factory()->create()->id);
        $tabFile = $this->createFile($this->visibleTab, 'tabbed.pdf');
        $tabFile->accessingUsers()->attach([User::factory()->create()->id, User::factory()->create()->id]);

        $migration = require database_path(
            'migrations/2026_10_06_140000_add_is_budget_document_to_project_files_table.php'
        );
        $migration->up();
        $migration->up();

        $this->assertTrue($sharedFile->fresh()->is_budget_document);
        $this->assertFalse($singleFile->fresh()->is_budget_document);
        $this->assertFalse($tabFile->fresh()->is_budget_document);
    }

    #[Test]
    public function contracts_documents_component_lists_only_contracts_the_viewer_may_open(): void
    {
        $this->placeInTab($this->visibleTab, $this->createSpecial('ProjectContractsDocumentsComponent'), 1);
        $member = $this->teamMember();
        $sharedContract = $this->createContract('Shared contract');
        $sharedContract->accessingUsers()->attach($member->id);
        $this->createContract('Secret contract');
        $ownContract = $this->createContract('Own contract');
        $ownContract->forceFill(['creator_id' => $member->id])->save();

        $contractNames = fn (): array => collect(
            $this->get($this->tabUrl($this->visibleTab))->assertOk()->viewData('page')['props']['projectContracts']
        )->pluck('name')->sort()->values()->all();

        $this->actingAs($member);
        $this->assertSame(['Own contract', 'Shared contract'], $contractNames());

        $manager = $this->teamMember();
        $this->project->users()->updateExistingPivot($manager->id, ['is_manager' => true]);
        $this->actingAs($manager);
        $this->assertSame(['Own contract', 'Secret contract', 'Shared contract'], $contractNames());

        $this->actingAsAdmin();
        $this->assertSame(['Own contract', 'Secret contract', 'Shared contract'], $contractNames());
    }

    /**
     * ContractEditModal sendet beim Speichern jedes vorbelegte Feld (ContractService füllt per fill()).
     * Fehlt ein Schlüssel im Payload, würde Speichern den Wert leeren – siehe
     * resources/js/Helper/contractEditForm.js (CONTRACT_EDIT_FIELDS) und tests/Frontend/contractEditForm.test.js.
     */
    #[Test]
    public function contract_payloads_carry_every_field_the_edit_modal_sends(): void
    {
        $this->placeInTab($this->visibleTab, $this->createSpecial('ProjectContractsDocumentsComponent'), 1);
        $this->createContract('Complete contract')->forceFill([
            'foreign_tax' => true,
            'foreign_tax_city' => 'Wien',
            'foreign_tax_country' => 'AT',
            'contract_state' => 'signed',
            'contract_state_comment' => 'liegt vor',
            'deadline_date' => '2026-10-06',
        ])->save();
        $this->actingAsAdmin();

        $projectTabRow = collect(
            $this->get($this->tabUrl($this->visibleTab))->assertOk()->viewData('page')['props']['projectContracts']
        )->firstWhere('name', 'Complete contract');
        foreach ([
            'name', 'partner', 'project', 'description', 'company_type', 'contract_type', 'currency', 'amount',
            'ksk_liable', 'ksk_amount', 'ksk_reason', 'resident_abroad', 'foreign_tax', 'foreign_tax_amount',
            'foreign_tax_city', 'foreign_tax_country', 'foreign_tax_reason', 'contract_state',
            'contract_state_comment', 'reverse_charge_amount', 'deadline_date', 'has_power_of_attorney', 'is_freed',
            'accessibleUsers', 'accessibleDepartments',
        ] as $field) {
            $this->assertArrayHasKey($field, $projectTabRow, $field);
        }
        $this->assertTrue($projectTabRow['foreign_tax']);
        $this->assertSame('Wien', $projectTabRow['foreign_tax_city']);
        $this->assertSame('signed', $projectTabRow['contract_state']);
        $this->assertSame($this->project->id, $projectTabRow['project']['id']);
        // Kalenderdatum statt UTC-Zeitpunkt (sonst verschiebt jedes Speichern die Frist um einen Tag)
        $this->assertSame('2026-10-06', $projectTabRow['deadline_date']);

        // Budget-Informationen liefern das rohe Modell; normalizeContractForEdit() bildet diese Namen ab
        $budgetContract = collect(
            $this->getJson(route('projects.tabs.budget-informations', $this->project))
                ->assertOk()
                ->json('BudgetInformation.contracts')
        )->firstWhere('name', 'Complete contract');
        foreach ([
            'contract_partner', 'project_id', 'foreign_tax', 'foreign_tax_city', 'foreign_tax_country',
            'contract_state', 'contract_state_comment', 'company_type', 'contract_type', 'currency',
            'accessing_users', 'accessing_departments',
        ] as $field) {
            $this->assertArrayHasKey($field, $budgetContract, $field);
        }
        $this->assertSame('2026-10-06', $budgetContract['deadline_date']);
        // Das Projekt wird nur für die Rechteprüfung angehängt, nicht je Vertrag ausgeliefert
        $this->assertArrayNotHasKey('project', $budgetContract);
    }

    #[Test]
    public function the_marker_saves_an_emptied_share_list_when_form_data_drops_the_empty_array(): void
    {
        $budgetUser = $this->teamMember(['access_budget' => true]);
        $otherUser = User::factory()->create();
        $file = $this->createFile(null, 'budget-marker.pdf', true);
        $file->accessingUsers()->attach([$budgetUser->id, $otherUser->id]);

        // FormData (Datei ersetzen) lässt ein leeres accessibleUsers weg – nur der Marker kommt an
        $this->actingAs($budgetUser);
        $this->post(route('project_files.update', $file), ['accessibleUsersSent' => '1'])->assertRedirect();

        $this->assertSame([$budgetUser->id], $file->fresh()->accessingUsers->pluck('id')->all());

        // ohne Liste und ohne Marker bleibt die Freigabe unverändert
        $file->accessingUsers()->attach($otherUser->id);
        $this->post(route('project_files.update', $file), [])->assertRedirect();
        $this->assertEqualsCanonicalizing(
            [$budgetUser->id, $otherUser->id],
            $file->fresh()->accessingUsers->pluck('id')->all()
        );
    }

    #[Test]
    public function correcting_a_share_list_does_not_add_the_acting_person(): void
    {
        $sharedUser = $this->teamMember(['access_budget' => true]);
        $otherUser = User::factory()->create();
        $file = $this->createFile(null, 'budget-correction.pdf', true);
        $file->accessingUsers()->attach([$sharedUser->id, $otherUser->id]);

        $admin = $this->actingAsAdmin();
        $this->postJson(route('project_files.update', $file), ['accessibleUsers' => [$sharedUser->id]])
            ->assertRedirect();

        $sharedIds = $file->fresh()->accessingUsers->pluck('id')->all();
        $this->assertSame([$sharedUser->id], $sharedIds);
        $this->assertNotContains($admin->id, $sharedIds);
    }

    #[Test]
    public function an_emptied_share_list_is_saved_and_keeps_the_acting_person(): void
    {
        $budgetUser = $this->teamMember(['access_budget' => true]);
        $otherUser = User::factory()->create();
        $file = $this->createFile(null, 'budget-emptied.pdf', true);
        $file->accessingUsers()->attach([$budgetUser->id, $otherUser->id]);

        $this->actingAs($budgetUser);
        $this->postJson(route('project_files.update', $file), ['accessibleUsers' => []])->assertRedirect();

        $this->assertSame([$budgetUser->id], $file->fresh()->accessingUsers->pluck('id')->all());
    }

    #[Test]
    public function print_layout_lists_only_contracts_the_viewer_may_open(): void
    {
        $member = $this->teamMember();
        $this->createContract('Shared print contract')->accessingUsers()->attach($member->id);
        $this->createContract('Secret print contract');

        $layout = $this->createLayout();
        $this->placeInLayout($layout, $this->createSpecial('ProjectContractsDocumentsComponent'), 1);
        $printedNames = fn (): array => collect(data_get(
            $this->get($this->printUrl($layout))->assertOk()->viewData('page')['props']['project'],
            'contracts_documents'
        ))->pluck('name')->sort()->values()->all();

        $this->actingAs($member);
        $this->assertSame(['Shared print contract'], $printedNames());

        $this->actingAsAdmin();
        $this->assertSame(['Secret print contract', 'Shared print contract'], $printedNames());
    }

    #[Test]
    public function checklists_of_hidden_tabs_are_only_viewable_with_tab_access_or_personal_link(): void
    {
        $member = $this->teamMember();
        $hiddenChecklist = $this->createChecklist($this->hiddenTab, 'hidden list');
        $visibleChecklist = $this->createChecklist($this->visibleTab, 'visible list');

        $this->assertFalse($member->can('view', $hiddenChecklist));
        $this->assertTrue($member->can('view', $visibleChecklist));

        // Ersteller:in und ausdrücklich geteilte Personen behalten den Zugriff
        $this->assertTrue($hiddenChecklist->user->can('view', $hiddenChecklist));
        $sharedMember = $this->teamMember();
        $hiddenChecklist->users()->attach($sharedMember->id);
        $this->assertTrue($sharedMember->fresh()->can('view', $hiddenChecklist->fresh()));

        $admin = $this->actingAsAdmin();
        $this->assertTrue($admin->can('view', $hiddenChecklist));
    }

    /**
     * @param array<string, bool> $pivot
     */
    private function teamMember(array $pivot = []): User
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user->id, array_merge(['can_write' => true], $pivot));

        return $user;
    }

    private function pdfUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'budget-document-');
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");

        return new UploadedFile($path, 'budget.pdf', null, null, true);
    }

    #[Test]
    public function checklists_of_hidden_tabs_cannot_be_changed_through_project_rights(): void
    {
        $writer = $this->teamMember();
        $hiddenChecklist = $this->createChecklist($this->hiddenTab, 'hidden list');
        $visibleChecklist = $this->createChecklist($this->visibleTab, 'visible list');

        $this->assertFalse($writer->can('update', $hiddenChecklist));
        $this->assertFalse($writer->can('delete', $hiddenChecklist));
        $this->assertTrue($writer->can('update', $visibleChecklist));
        $this->assertTrue($writer->can('delete', $visibleChecklist));

        $this->actingAs($writer);
        $this->patch(route('checklists.update', $hiddenChecklist), ['name' => 'renamed'])->assertForbidden();
        $this->delete(route('checklist.destroy', $hiddenChecklist))->assertForbidden();
        $this->assertSame('hidden list', $hiddenChecklist->fresh()->name);

        // Ersteller:in und direkt geteilte Personen behalten ihren Zugriff
        $owner = $hiddenChecklist->user;
        $this->assertTrue($owner->can('update', $hiddenChecklist));
        $this->assertTrue($owner->can('delete', $hiddenChecklist));
        $sharedMember = $this->teamMember();
        $hiddenChecklist->users()->attach($sharedMember->id);
        $this->assertTrue($sharedMember->can('update', $hiddenChecklist->fresh()));

        $this->assertTrue($this->adminUser()->can('delete', $hiddenChecklist));
    }

    private function placeAllComponentsInVisibleTab(): void
    {
        $this->placeInTab($this->visibleTab, $this->createSpecial(ProjectTabComponentEnum::COMMENT_ALL_TAB->value), 90);
        $this->placeInTab($this->visibleTab, $this->createSpecial(ProjectTabComponentEnum::CHECKLIST_ALL->value), 91);
        $this->placeInTab(
            $this->visibleTab,
            $this->createSpecial(ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS->value),
            92
        );
    }

    private function createContract(string $name): Contract
    {
        return Contract::factory()->create([
            'name' => $name,
            'project_id' => $this->project->id,
        ]);
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

    private function createFile(?ProjectTab $tab, string $name, bool $isBudgetDocument = false): ProjectFile
    {
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $this->project->id,
            'tab_id' => $tab?->id,
            'name' => $name,
            'basename' => uniqid() . $name,
            'is_budget_document' => $isBudgetDocument,
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
