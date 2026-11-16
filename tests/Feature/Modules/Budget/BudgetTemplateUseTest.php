<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Budgetvorlage in ein Projekt übernehmen: die bisherige Projekttabelle wird ersetzt,
 * die Vorlage bleibt unverändert. Fehlende Projekt-Id, Projekte ohne Tabelle und
 * Nicht-Vorlagen als Quelle dürfen weder zu einem 500er noch zu Datenverlust führen.
 */
final class BudgetTemplateUseTest extends FeatureTestCase
{
    private function createTable(?int $projectId, bool $isTemplate, string $mainPositionName): Table
    {
        $table = Table::factory()->create([
            'project_id' => $projectId,
            'is_template' => $isTemplate,
            'name' => $mainPositionName . ' Tabelle',
        ]);
        Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'name' => 'KTO']);
        Column::factory()->create(['table_id' => $table->id, 'position' => 1, 'name' => 'Wert']);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id, 'name' => $mainPositionName]);
        SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);

        return $table;
    }

    #[Test]
    public function using_a_template_replaces_the_project_table_with_a_copy(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $oldTable = $this->createTable($project->id, false, 'Alt');
        $template = $this->createTable(null, true, 'Vorlage');

        $this->post(route('project.budget.template.use', $template->id), ['project_id' => $project->id])
            ->assertRedirect();

        $newTable = $project->table()->firstOrFail();
        $this->assertNotSame($oldTable->id, $newTable->id);
        $this->assertFalse((bool) $newTable->is_template);
        $this->assertSame('Vorlage Tabelle', $newTable->name);
        $this->assertSame(['Vorlage'], $newTable->mainPositions()->pluck('name')->all());
        $this->assertSame(2, $newTable->columns()->count());
        $this->assertNull(Table::query()->find($oldTable->id));

        // Die Vorlage selbst bleibt erhalten
        $this->assertTrue((bool) $template->fresh()->is_template);
        $this->assertSame(1, $template->mainPositions()->count());
    }

    #[Test]
    public function a_project_member_with_budget_access_can_import_a_template(): void
    {
        $project = Project::factory()->create();
        $this->createTable($project->id, false, 'Alt');
        $template = $this->createTable(null, true, 'Vorlage');
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $this->post(route('project.budget.template.use', $template->id), ['project_id' => $project->id])
            ->assertRedirect();

        $this->assertSame(['Vorlage'], $project->table()->firstOrFail()->mainPositions()->pluck('name')->all());
    }

    #[Test]
    public function using_a_template_on_a_project_without_table_creates_one(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $template = $this->createTable(null, true, 'Vorlage');

        $this->post(route('project.budget.template.use', $template->id), ['project_id' => $project->id])
            ->assertRedirect();

        $this->assertSame(['Vorlage'], $project->table()->firstOrFail()->mainPositions()->pluck('name')->all());
    }

    #[Test]
    public function using_a_template_without_project_id_is_a_validation_error(): void
    {
        $this->actingAsAdmin();
        $template = $this->createTable(null, true, 'Vorlage');
        $tableCount = Table::query()->count();

        $this->postJson(route('project.budget.template.use', $template->id), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');

        $this->assertSame($tableCount, Table::query()->count());
    }

    #[Test]
    public function using_the_project_table_itself_as_template_is_rejected_and_keeps_the_budget(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $projectTable = $this->createTable($project->id, false, 'Eigenes Budget');

        $this->post(route('project.budget.template.use', $projectTable->id), ['project_id' => $project->id])
            ->assertNotFound();

        $this->assertSame($projectTable->id, $project->table()->firstOrFail()->id);
        $this->assertSame(['Eigenes Budget'], $projectTable->mainPositions()->pluck('name')->all());
    }
}
