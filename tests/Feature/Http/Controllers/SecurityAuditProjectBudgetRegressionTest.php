<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regressionstests zum Sicherheits-Audit 21.09.2026, Abschnitt A
 * (Autorisierung Projekt / Termin / Finanzen / Budget).
 */
final class SecurityAuditProjectBudgetRegressionTest extends FeatureTestCase
{
    /**
     * @return array{0: Table, 1: Column}
     */
    private function createProjectTableWithColumn(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }
        $column = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);

        return [$table->refresh(), $column];
    }

    private function userWithBudgetAccess(Project $project): User
    {
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        return $user;
    }

    // ---------- KRITISCH: Budget-Exporte ----------

    #[Test]
    public function budget_export_by_deadline_requires_global_budget_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('projects.export.budgetByBudgetDeadline', [
            'startBudgetDeadline' => '2026-01-01',
            'endBudgetDeadline' => '2026-12-31',
            'type' => 0,
        ]))->assertForbidden();

        $this->actingAsUserWith('can manage global project budgets');

        $this->get(route('projects.export.budgetByBudgetDeadline', [
            'startBudgetDeadline' => '2026-01-01',
            'endBudgetDeadline' => '2026-12-31',
            'type' => 0,
        ]))->assertSuccessful();
    }

    #[Test]
    public function project_budget_export_requires_budget_access_on_that_project(): void
    {
        [$table] = $this->createProjectTableWithColumn();
        [$foreignTable] = $this->createProjectTableWithColumn();

        $this->actingAs(User::factory()->create());
        $this->get(route('projects.export.budget', $table->project))->assertForbidden();

        $this->userWithBudgetAccess($table->project);
        $this->get(route('projects.export.budget', $foreignTable->project))->assertForbidden();
        $this->get(route('projects.export.budget', $table->project))->assertSuccessful();
    }

    // ---------- HOCH: Budgetvorlage aus fremdem Projekt ----------

    #[Test]
    public function budget_template_from_another_project_requires_budget_access_on_the_source(): void
    {
        [$ownTable] = $this->createProjectTableWithColumn();
        [$foreignTable] = $this->createProjectTableWithColumn();
        $user = $this->userWithBudgetAccess($ownTable->project);

        $this->post(route('project.budget.template.project'), [
            'project_id' => $ownTable->project_id,
            'template_project_id' => $foreignTable->project_id,
        ])->assertForbidden();

        $foreignTable->project->users()->attach($user->id, ['access_budget' => true]);

        $this->post(route('project.budget.template.project'), [
            'project_id' => $ownTable->project_id,
            'template_project_id' => $foreignTable->project_id,
        ])->assertSuccessful();
    }

    // ---------- HOCH: Zeilen aus fremdem Budget ziehen ----------

    #[Test]
    public function rows_of_a_foreign_budget_cannot_be_pulled_into_an_own_sub_position(): void
    {
        [$ownTable] = $this->createProjectTableWithColumn();
        [$foreignTable] = $this->createProjectTableWithColumn();
        $this->userWithBudgetAccess($ownTable->project);

        $ownMain = MainPosition::factory()->create(['table_id' => $ownTable->id]);
        $ownSub = SubPosition::factory()->create(['main_position_id' => $ownMain->id]);
        $foreignMain = MainPosition::factory()->create(['table_id' => $foreignTable->id]);
        $foreignSub = SubPosition::factory()->create(['main_position_id' => $foreignMain->id]);
        $foreignRow = SubPositionRow::factory()->create(['sub_position_id' => $foreignSub->id, 'position' => 0]);

        $this->patch(route('project.budget.sub-position-row.reorder'), [
            'updates' => [
                ['sub_position_id' => $ownSub->id, 'row_ids' => [$foreignRow->id]],
            ],
        ])->assertForbidden();

        $this->assertDatabaseHas('sub_position_rows', ['id' => $foreignRow->id, 'sub_position_id' => $foreignSub->id]);
    }

    // ---------- HOCH: Projektgruppen ----------

    #[Test]
    public function project_group_membership_changes_require_write_access_on_the_group(): void
    {
        $group = Project::factory()->create(['is_group' => true]);
        $project = Project::factory()->create();
        $group->projectsOfGroup()->attach($project->id);

        $this->actingAs(User::factory()->create());
        $this->postJson(route('project-group.add-projects', $group), [
            'projectIdsToAdd' => [['id' => $project->id]],
        ])->assertForbidden();
        $this->assertDatabaseHas('project_groups', ['group_id' => $group->id, 'project_id' => $project->id]);

        // projects.group.delete (deleteProjectFromGroup) wurde entfernt: kein Frontend-Aufrufer,
        // Parameter waren vertauscht benannt (Sicherheits-Audit 21.09.2026).
        $this->actingAsUserWith('write projects');
        $this->postJson(route('project-group.add-projects', $group), [
            'projectIdsToAdd' => [['id' => $project->id]],
        ])->assertSuccessful();
        $this->assertDatabaseHas('project_groups', ['group_id' => $group->id, 'project_id' => $project->id]);
    }

    // ---------- HOCH: Kostenstelle / Copyright ----------

    #[Test]
    public function cost_center_and_copyright_require_write_access_on_the_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->postJson(route('projects.update.cost-center', $project), ['cost_center_name' => 'KST-1'])
            ->assertForbidden();
        $this->postJson(route('project.copyright.update', $project), ['cost_center_name' => 'KST-1', 'gema' => true])
            ->assertForbidden();
        $this->assertNull($project->fresh()->cost_center_id);

        $this->actingAsUserWith('write projects');
        $this->post(route('projects.update.cost-center', $project), ['cost_center_name' => 'KST-1'])
            ->assertRedirect();
        $this->post(route('project.copyright.update', $project), ['cost_center_name' => 'KST-1', 'gema' => true])
            ->assertRedirect();
        $this->assertNotNull($project->fresh()->cost_center_id);
    }

    // ---------- HOCH: Finanzierungsquellen an Budgetsummen ----------

    #[Test]
    public function sum_money_sources_require_budget_access_and_an_allowlisted_target(): void
    {
        [$table, $column] = $this->createProjectTableWithColumn();
        $sumDetail = BudgetSumDetails::factory()->create(['column_id' => $column->id]);
        $moneySource = MoneySource::factory()->create();
        $payload = [
            'linked_type' => 'budget',
            'money_source_id' => $moneySource->id,
            'sourceable_id' => $sumDetail->id,
            'sourceable_type' => BudgetSumDetails::class,
        ];

        $this->actingAs(User::factory()->create());
        $this->postJson(route('project.sum.money.source.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('sum_money_sources', 0);

        $this->userWithBudgetAccess($table->project);
        $this->postJson(route('project.sum.money.source.store'), $payload)->assertSuccessful();
        $this->assertDatabaseCount('sum_money_sources', 1);

        // Beliebige Morph-Klasse im Body: auch für Admins nicht erlaubt
        $this->actingAsAdmin();
        $this->postJson(route('project.sum.money.source.store'), array_merge($payload, [
            'sourceable_type' => User::class,
            'sourceable_id' => 1,
        ]))->assertUnprocessable();
    }

    // ---------- HOCH: Summen-Kommentare ----------

    #[Test]
    public function sum_comments_require_budget_access_and_only_the_author_may_delete(): void
    {
        [$table, $column] = $this->createProjectTableWithColumn();
        $sumDetail = BudgetSumDetails::factory()->create(['column_id' => $column->id]);
        $payload = [
            'comment' => 'Rückfrage',
            'commentable_id' => $sumDetail->id,
            'commentable_type' => BudgetSumDetails::class,
        ];

        $this->actingAs(User::factory()->create());
        $this->postJson(route('sum.comments.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('sum_comments', 0);

        $author = $this->userWithBudgetAccess($table->project);
        $this->postJson(route('sum.comments.store'), $payload)->assertSuccessful();
        $comment = SumComment::query()->firstOrFail();
        $this->assertSame($author->id, $comment->user_id);

        // Beliebige Morph-Klasse im Body: auch für Admins nicht erlaubt
        $this->actingAsAdmin();
        $this->postJson(route('sum.comments.store'), array_merge($payload, ['commentable_type' => User::class]))
            ->assertUnprocessable();

        // Anderes Budget-Teammitglied darf fremden Kommentar nicht löschen
        $this->userWithBudgetAccess($table->project);
        $this->deleteJson(route('sum.comments.delete', $comment))->assertForbidden();
        $this->assertNotSoftDeleted($comment);

        $this->actingAs($author);
        $this->deleteJson(route('sum.comments.delete', $comment))->assertSuccessful();
        $this->assertSoftDeleted($comment);
    }

    // ---------- HOCH: Globale Termin-Standardwerte ----------

    #[Test]
    public function event_standard_values_require_event_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('event.standard.values'))->assertForbidden();
        $this->patch(route('event.standard.values.update'), ['event_time_length_minutes' => 30])->assertForbidden();

        $this->actingAsUserWith('change event settings');
        $this->get(route('event.standard.values'))->assertOk();
        $this->patch(route('event.standard.values.update'), ['event_time_length_minutes' => 30])->assertSuccessful();
    }

    // ---------- HOCH: Projekt-Anlage-/Aufenthalts-Settings ----------

    #[Test]
    public function project_creation_settings_require_project_settings_permission(): void
    {
        $settingsPayload = [
            'attributes' => true,
            'state' => true,
            'managers' => true,
            'cost_center' => true,
            'budget_deadline' => true,
        ];
        $residencyPayload = [
            'breakfast_deduction_per_day' => 5,
            'artist_residency_do_not_save_default' => false,
            'artist_residency_daily_allowance_default' => 28,
            'artist_residency_name_columns' => [['key' => 'name', 'enabled' => true]],
        ];

        $this->actingAs(User::factory()->create());
        $this->patch(route('project_settings.update'), $settingsPayload)->assertForbidden();
        $this->patch(route('project_settings.artist_residency.update'), $residencyPayload)->assertForbidden();

        $this->actingAsUserWith('change project settings');
        $this->patch(route('project_settings.update'), $settingsPayload)->assertRedirect();
        $this->patch(route('project_settings.artist_residency.update'), $residencyPayload)->assertRedirect();
    }

    // ---------- HOCH: Checkliste duplizieren ----------

    #[Test]
    public function duplicating_a_checklist_requires_write_access_and_never_joins_the_project_team(): void
    {
        $checklist = Checklist::factory()->create();
        $project = $checklist->project;

        $outsider = User::factory()->create();
        $this->actingAs($outsider);
        $this->post(route('checklists.duplicate', $checklist))->assertForbidden();
        $this->assertFalse($project->users()->whereKey($outsider->id)->exists());
        $this->assertSame(1, Checklist::query()->count());

        // "To-dos verwalten" erlaubt das Duplizieren fremder Listen — ohne Selbstaufnahme ins Team
        $editor = $this->actingAsUserWith('can edit checklist');
        $this->post(route('checklists.duplicate', $checklist))->assertRedirect();
        $this->assertSame(2, Checklist::query()->count());
        $this->assertFalse($project->users()->whereKey($editor->id)->exists());
    }

    // ---------- HOCH: Termin-Papierkorb ----------

    #[Test]
    public function event_trash_actions_require_trash_access_and_delete_permission(): void
    {
        $event = Event::factory()->create();
        $event->delete();

        $this->actingAs(User::factory()->create());
        $this->patch(route('events.restore', ['id' => $event->id]))->assertForbidden();
        $this->delete(route('events.force.all'))->assertForbidden();
        $this->assertSoftDeleted('events', ['id' => $event->id]);

        // Papierkorb-Zugriff allein reicht für das Wiederherstellen nicht (EventPolicy::delete)
        $this->actingAsUserWith('can access trash');
        $this->patch(route('events.restore', ['id' => $event->id]))->assertForbidden();
        $this->assertSoftDeleted('events', ['id' => $event->id]);

        $this->actingAsUserWith(['can access trash', 'create events without request']);
        $this->patch(route('events.restore', ['id' => $event->id]))->assertRedirect();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);

        $event->delete();
        $this->delete(route('events.force.all'))->assertRedirect();
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    // ---------- HOCH: Vertrag an beliebigem Projekt ----------

    #[Test]
    public function storing_a_contract_requires_project_write_access_and_a_matching_document_request(): void
    {
        $project = Project::factory()->create();
        $foreignProject = Project::factory()->create();
        $payload = fn (): array => [
            'file' => UploadedFile::fake()->create('vertrag.pdf', 10, 'application/pdf'),
            'contract_partner' => 'Partner GmbH',
        ];

        $this->actingAs(User::factory()->create());
        $this->post(route('contracts.store', $project), $payload())->assertForbidden();
        $this->assertDatabaseCount('contracts', 0);

        $writer = $this->actingAsUserWith('write projects');

        // Dokumentenanfrage eines ANDEREN Projekts darf nicht verknüpft werden
        $foreignRequest = DocumentRequest::query()->create([
            'requester_id' => $writer->id,
            'requested_id' => $writer->id,
            'project_id' => $foreignProject->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        $this->post(route('contracts.store', $project), array_merge($payload(), [
            'document_request_id' => $foreignRequest->id,
        ]))->assertForbidden();
        $this->assertDatabaseCount('contracts', 0);

        $this->post(route('contracts.store', $project), $payload())->assertRedirect();
        $this->assertSame(1, Contract::query()->where('project_id', $project->id)->count());
    }
}
