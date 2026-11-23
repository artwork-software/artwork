<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die Budgettabelle löscht mit Bedenkzeit ("Rückgängig") per axios: die Löschrouten antworten
 * dann schlank mit JSON, Inertia-Aufrufe werden weiterhin zurückgeleitet.
 */
final class BudgetDeleteResponseTest extends FeatureTestCase
{
    /**
     * @return array{0: Table, 1: MainPosition, 2: SubPosition, 3: SubPositionRow}
     */
    private function createBudgetStructure(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        foreach ([0, 1, 2, 3, 4] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position, 'type' => 'empty']);
        }
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id, 'type' => 'BUDGET_TYPE_COST']);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        foreach ($table->columns()->get() as $column) {
            ColumnCell::factory()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $row->id,
                'value' => '0,00',
            ]);
        }

        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        return [$table->refresh(), $mainPosition, $subPosition, $row];
    }

    #[Test]
    public function row_delete_answers_json_requests_without_redirect(): void
    {
        [, , , $row] = $this->createBudgetStructure();

        $this->deleteJson(route('project.budget.sub-position-row.delete', $row->id))
            ->assertOk()
            ->assertExactJson(['message' => null]);

        $this->assertDatabaseMissing('sub_position_rows', ['id' => $row->id]);
    }

    #[Test]
    public function row_delete_still_redirects_inertia_requests(): void
    {
        [, , , $row] = $this->createBudgetStructure();

        $this->delete(route('project.budget.sub-position-row.delete', $row->id))->assertRedirect();
        $this->assertDatabaseMissing('sub_position_rows', ['id' => $row->id]);
    }

    #[Test]
    public function inertia_requests_that_accept_json_are_still_redirected(): void
    {
        [, , , $row] = $this->createBudgetStructure();

        $this->withHeaders(['X-Inertia' => 'true', 'Accept' => 'application/json'])
            ->delete(route('project.budget.sub-position-row.delete', $row->id))
            ->assertRedirect();
    }

    #[Test]
    public function position_deletes_answer_json_requests(): void
    {
        [, $mainPosition, $subPosition] = $this->createBudgetStructure();

        $this->deleteJson(route('project.budget.sub-position.delete', $subPosition->id))->assertOk();
        $this->assertDatabaseMissing('sub_positions', ['id' => $subPosition->id]);

        $this->deleteJson(route('project.budget.main-position.delete', $mainPosition->id))->assertOk();
        $this->assertDatabaseMissing('main_positions', ['id' => $mainPosition->id]);
    }

    #[Test]
    public function column_delete_reports_when_the_last_value_column_was_emptied_instead(): void
    {
        [$table] = $this->createBudgetStructure();
        $valueColumns = $table->columns()->orderBy('position')->get()->skip(3)->values();

        $this->deleteJson(route('project.budget.column.delete', $valueColumns[0]->id))
            ->assertOk()
            ->assertExactJson(['message' => null]);
        $this->assertSoftDeleted('columns', ['id' => $valueColumns[0]->id]);

        $response = $this->deleteJson(route('project.budget.column.delete', $valueColumns[1]->id))->assertOk();
        $this->assertNotNull($response->json('message'));
        $this->assertDatabaseHas('columns', ['id' => $valueColumns[1]->id, 'deleted_at' => null]);
    }
}
