<?php

namespace Tests\Feature;

use App\Http\Controllers\SageNotAssignedDataController;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Illuminate\Support\Facades\Validator;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 31.10.2026: Pfad-Traversal in Inventar-Dateieigenschaften (F-01), implizite Teamaufnahme mit
 * Schreibrecht über Aufgaben/Checklisten (F-02), Budget-Vorlagen über fremde project_id (F-03),
 * Budgetzugriff über Prüfanfragen (F-04), Dokumentanfragen an fremde Projekte (F-06) und
 * Planungstermine über die App-API (F-07).
 */
final class SecurityAuditBusinessLogicRegressionTest extends FeatureTestCase
{
    private string $diskRoot;

    /**
     * Eigenes Wurzelverzeichnis, damit die Löschprüfung echte Verzeichnisse sieht.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = storage_path('framework/testing/disks/security-audit-business-logic-' . uniqid());
        Storage::set('local', Storage::build(['driver' => 'local', 'root' => $this->diskRoot]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->diskRoot);

        parent::tearDown();
    }

    private function pivot(Project $project, User $user): ?object
    {
        return $project->users()->whereKey($user->id)->first()?->pivot;
    }

    // ---------- F-01: Pfad-Traversal in Dateieigenschaften ----------

    #[Test]
    public function a_traversal_path_cannot_be_stored_as_file_property_value(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $detail = InventoryDetailedQuantityArticle::factory()->create();
        $property = InventoryArticleProperties::factory()->create(['type' => 'file']);

        foreach (['uploads/inventory-properties/../../public', 'project_files/x/vertrag.pdf'] as $value) {
            $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), [
                'property_id' => $property->id,
                'value' => $value,
            ])->assertUnprocessable()->assertJsonValidationErrors('value');
        }

        $this->assertSame(0, $detail->properties()->count());
    }

    #[Test]
    public function article_properties_must_be_lists_so_the_file_check_sees_the_property(): void
    {
        $property = InventoryArticleProperties::factory()->create(['type' => 'file']);
        $rules = (new StoreInventoryArticleRequest())->rules();

        $validator = Validator::make([
            'properties' => ['a.b' => ['id' => $property->id, 'value' => 'uploads/inventory-properties/../../public']],
        ], array_intersect_key($rules, array_flip(['properties', 'properties.*.id', 'properties.*.value'])));

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function a_valid_upload_path_and_non_file_values_are_still_accepted(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $detail = InventoryDetailedQuantityArticle::factory()->create();
        $fileProperty = InventoryArticleProperties::factory()->create(['type' => 'file']);
        $textProperty = InventoryArticleProperties::factory()->create(['type' => 'string']);

        $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), [
            'property_id' => $fileProperty->id,
            'value' => 'uploads/inventory-properties/0b5c1f9e-7f3a-4a59-9d55-6f0c2c1d2e3f/handbuch.pdf',
        ])->assertOk();
        $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), [
            'property_id' => $textProperty->id,
            'value' => '../../nur/text',
        ])->assertOk();

        $this->assertSame(2, $detail->properties()->count());
    }

    #[Test]
    public function force_deleting_an_article_with_a_planted_traversal_value_keeps_other_files(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_DELETE->value);
        $article = InventoryArticle::factory()->create();
        $property = InventoryArticleProperties::factory()->create(['type' => 'file']);
        Storage::disk('local')->put('public/logo.png', 'x');
        Storage::disk('local')->put('project_files/x/vertrag.pdf', 'x');
        Storage::disk('local')->put('uploads/inventory-properties/abc/handbuch.pdf', 'x');
        // Altbestand: vor der Validierung gespeicherte Werte.
        foreach (['uploads/inventory-properties/../../public', 'uploads/inventory-properties/abc/handbuch.pdf'] as $value) {
            InventoryPropertyValue::query()->create([
                'inventory_propertyable_type' => InventoryArticle::class,
                'inventory_propertyable_id' => $article->id,
                'inventory_article_property_id' => $property->id,
                'value' => $value,
            ]);
        }
        $article->delete();

        $this->delete(route('articles.forceDelete', $article->id))->assertOk();

        Storage::disk('local')->assertExists('public/logo.png');
        Storage::disk('local')->assertExists('project_files/x/vertrag.pdf');
        Storage::disk('local')->assertMissing('uploads/inventory-properties/abc/handbuch.pdf');
    }

    // ---------- F-02: Implizite Teamaufnahme nur ohne Schreibrecht ----------

    #[Test]
    public function global_checklist_editor_cannot_join_a_foreign_project_via_a_task(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
        $project = Project::factory()->create();
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'user_id' => null]);

        $this->post(route('tasks.store'), [
            'checklist_id' => $checklist->id,
            'name' => 'x',
            'users' => [$user->id],
        ])->assertRedirect();

        $this->assertNull($this->pivot($project, $user));
    }

    #[Test]
    public function read_only_member_cannot_pull_users_into_the_project_via_a_checklist(): void
    {
        $member = $this->actingAsUserWith([]);
        $outsider = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($member->id, ['can_write' => false]);
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'user_id' => null]);

        $this->patch(route('checklists.update', $checklist), [
            'assigned_user_ids' => [$member->id, $outsider->id],
        ]);

        $this->assertNull($this->pivot($project, $outsider));
        $this->assertFalse((bool) $this->pivot($project, $member)->can_write);
    }

    #[Test]
    public function project_writer_adds_task_users_as_read_only_members(): void
    {
        $writer = $this->actingAsUserWith([]);
        $assignee = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($writer->id, ['can_write' => true]);
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'user_id' => null]);

        $this->post(route('tasks.store'), [
            'checklist_id' => $checklist->id,
            'name' => 'x',
            'users' => [$assignee->id],
        ])->assertRedirect();

        $pivot = $this->pivot($project, $assignee);
        $this->assertNotNull($pivot);
        $this->assertFalse((bool) $pivot->can_write);
        $this->assertFalse((bool) $pivot->access_budget);

        $task = Task::query()->where('checklist_id', $checklist->id)->firstOrFail();
        $outsider = User::factory()->create();
        $this->patch(route('tasks.update', $task), ['name' => 'y', 'users' => [$outsider->id]]);

        $this->assertFalse((bool) $this->pivot($project, $outsider)?->can_write);
    }

    // ---------- F-03: Vorlagen-Objekte nicht über eine eigene project_id ----------

    #[Test]
    public function own_project_id_does_not_unlock_budget_templates(): void
    {
        $ownProject = Project::factory()->create();
        $user = $this->actingAsUserWith([]);
        $ownProject->users()->attach($user->id, ['access_budget' => true]);
        $template = Table::factory()->create(['project_id' => null, 'is_template' => true]);

        $this->delete(route('project.budget.table.delete', [$template, 'project_id' => $ownProject->id]))
            ->assertForbidden();
        $this->patch(route('project.budget.table.update-name'), [
            'table_id' => $template->id,
            'table_name' => 'gekapert',
            'project_id' => $ownProject->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('tables', ['id' => $template->id, 'name' => $template->name]);
    }

    #[Test]
    public function global_budget_cleanups_need_their_own_permissions(): void
    {
        $ownProject = Project::factory()->create();
        $user = $this->actingAsUserWith([]);
        $ownProject->users()->attach($user->id, ['access_budget' => true]);

        $this->patch(route('project.budget.updateTableColumnOrders'), [
            'tableColumnOrders' => [],
            'project_id' => $ownProject->id,
        ])->assertForbidden();

        $trashed = SageNotAssignedData::factory()->create(['project_id' => null]);
        $trashed->delete();
        $this->delete(route('sageNotAssignedData.forceDeleteAll', ['project_id' => $ownProject->id]))
            ->assertClientError();
        $this->assertSoftDeleted($trashed);
    }

    // ---------- F-04: Prüfanfrage vergibt keinen Budgetzugriff auf fremde Projekte ----------

    #[Test]
    public function verification_request_for_a_foreign_position_is_forbidden(): void
    {
        $ownTable = Table::factory()->create(['is_template' => false]);
        $foreignPosition = MainPosition::factory()->create();
        $foreignProject = $foreignPosition->table->project;
        $user = $this->actingAsUserWith(PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value);
        $ownTable->project->users()->attach($user->id, ['access_budget' => true]);
        $foreignProject->users()->attach($user->id, ['access_budget' => false]);

        $this->post(route('project.budget.verified.main-position.request'), [
            'id' => $foreignPosition->id,
            'user' => $user->id,
            'giveBudgetAccess' => true,
            'project_id' => $ownTable->project_id,
        ])->assertForbidden();

        $foreignSub = SubPosition::factory()->create(['main_position_id' => $foreignPosition->id]);
        $this->post(route('project.budget.verified.sub-position.request'), [
            'id' => $foreignSub->id,
            'user' => $user->id,
            'giveBudgetAccess' => true,
            'project_id' => $ownTable->project_id,
        ])->assertForbidden();

        $this->assertFalse((bool) $this->pivot($foreignProject, $user)->access_budget);
    }

    #[Test]
    public function a_fractional_id_cannot_slip_a_foreign_position_past_the_budget_check(): void
    {
        $ownTable = Table::factory()->create(['is_template' => false]);
        $foreignPosition = MainPosition::factory()->create();
        $user = $this->actingAsUserWith(PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value);
        $ownTable->project->users()->attach($user->id, ['access_budget' => true]);

        $this->post(route('project.budget.verified.main-position.request'), [
            'id' => $foreignPosition->id . '.9',
            'user' => $user->id,
            'project_id' => $ownTable->project_id,
        ])->assertForbidden();

        $this->assertSame('BUDGET_VERIFIED_TYPE_NOT_VERIFIED', $foreignPosition->fresh()->is_verified);
    }

    #[Test]
    public function sum_details_need_position_and_column_from_the_same_table(): void
    {
        $ownTable = Table::factory()->create(['is_template' => false]);
        $ownColumn = Column::factory()->create(['table_id' => $ownTable->id]);
        $foreignPosition = MainPosition::factory()->create();
        $user = $this->actingAsUserWith([]);
        $ownTable->project->users()->attach($user->id, ['access_budget' => true]);

        $this->getJson(route('project.budget.sum-details.show', [
            'type' => 'mainPosition',
            'position_id' => $foreignPosition->id,
            'column_id' => $ownColumn->id,
        ]))->assertNotFound();

        $this->assertDatabaseMissing('main_position_details', ['main_position_id' => $foreignPosition->id]);
    }

    #[Test]
    public function emptying_the_sage_trash_only_removes_data_of_accessible_projects(): void
    {
        $ownProject = Project::factory()->create();
        $foreignProject = Project::factory()->create();
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value);
        $ownProject->users()->attach($user->id, ['access_budget' => true]);
        $own = SageNotAssignedData::factory()->create(['project_id' => $ownProject->id]);
        $foreign = SageNotAssignedData::factory()->create(['project_id' => $foreignProject->id]);
        $own->delete();
        $foreign->delete();

        app(SageNotAssignedDataController::class)->forceDeleteAll(request()->setUserResolver(fn () => $user));

        $this->assertDatabaseMissing('sage_not_assigned_data', ['id' => $own->id]);
        $this->assertSoftDeleted($foreign);
    }

    #[Test]
    public function budget_member_without_lead_rights_cannot_grant_budget_access_with_a_request(): void
    {
        $position = MainPosition::factory()->create();
        $project = $position->table->project;
        $user = $this->actingAsUserWith(PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value);
        $colleague = User::factory()->create();
        $project->users()->attach($user->id, ['access_budget' => true]);
        $project->users()->attach($colleague->id, ['access_budget' => false]);

        $this->post(route('project.budget.verified.main-position.request'), [
            'id' => $position->id,
            'user' => $colleague->id,
            'giveBudgetAccess' => true,
        ]);

        $this->assertFalse((bool) $this->pivot($project, $colleague)->access_budget);
    }

    // ---------- F-06: Dokumentanfragen nur an sichtbare Projekte ----------

    #[Test]
    public function document_request_cannot_reference_an_invisible_project(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::DOCUMENT_REQUEST_CREATE->value);
        $foreignProject = Project::factory()->create();

        $this->post(route('document-requests.store'), [
            'project_id' => $foreignProject->id,
            'requested_id' => $user->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('document_requests', ['project_id' => $foreignProject->id]);
    }

    #[Test]
    public function document_request_cannot_be_moved_to_an_invisible_project(): void
    {
        $user = $this->actingAsUserWith([
            PermissionEnum::DOCUMENT_REQUEST_CREATE->value,
            PermissionEnum::DOCUMENT_REQUEST_EDIT->value,
        ]);
        $documentRequest = DocumentRequest::factory()->create(['requester_id' => $user->id, 'project_id' => null]);
        $foreignProject = Project::factory()->create();

        $this->patch(route('document-requests.update', $documentRequest), ['project_id' => $foreignProject->id])
            ->assertForbidden();

        $this->assertNull($documentRequest->fresh()->project_id);
    }

    // ---------- F-07: Planungstermine über die App-API ----------

    #[Test]
    public function app_api_does_not_return_planned_events_without_planning_right(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $planned = Event::factory()->create(['project_id' => $project->id, 'is_planning' => true]);
        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.events.show', [$project, $planned]))->assertForbidden();
    }

    // ---------- Härtung (I-01, I-05, I-07, I-09) ----------

    #[Test]
    public function oidc_sources_only_accept_https_discovery_urls(): void
    {
        $this->actingAsAdmin();

        $this->post(route('tool.external-user-management.sources.store'), [
            'name' => 'IdP',
            'active' => false,
            'type' => 'identity_provider',
            'config' => [
                'discovery_url' => 'http://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'secret',
            ],
        ])->assertSessionHasErrors('config.discovery_url');
    }

    #[Test]
    public function team_editor_cannot_create_a_team_containing_themselves(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::TEAM_UPDATE->value);

        $this->post(route('departments.store'), [
            'name' => 'Selbstbedienung',
            'svg_name' => 'lightSvg',
            'assigned_users' => [['id' => $user->id]],
        ])->assertSessionHasErrors('assigned_users');

        $this->assertDatabaseMissing('departments', ['name' => 'Selbstbedienung']);
    }

    #[Test]
    public function clockwork_has_no_known_or_empty_default_password(): void
    {
        $password = (string) config('clockwork.authentication_password');

        $this->assertNotSame('', $password);
        $this->assertNotSame('VerySecretPassword', $password);
    }

    #[Test]
    public function js_permissions_are_rendered_html_safe(): void
    {
        $blade = file_get_contents(resource_path('views/app.blade.php'));

        $this->assertStringContainsString('@json(auth()->check() ? auth()->user()->objPermissions() : 0)', $blade);
        $this->assertStringNotContainsString('jsPermissions():0 !!}', $blade);
    }
}
