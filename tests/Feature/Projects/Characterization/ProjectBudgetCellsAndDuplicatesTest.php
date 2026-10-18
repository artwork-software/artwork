<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Zell-Endpunkte (Kalkulationen, Finanzierungsquelle) und des Duplizierens
 * von Zeilen/Unterpositionen im Projektbudget. Zugriff nur mit access_budget am Projekt.
 */
final class ProjectBudgetCellsAndDuplicatesTest extends FeatureTestCase
{
    private Project $project;

    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        app(BudgetService::class)->generateBasicBudgetValues($this->project);
        $this->table = Table::query()->where('project_id', $this->project->id)->sole();
    }

    private function actingAsBudgetMember(): User
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        return $user;
    }

    private function subPosition(): SubPosition
    {
        return SubPosition::query()
            ->whereIn('main_position_id', $this->table->mainPositions()->pluck('id'))
            ->orderBy('id')
            ->firstOrFail();
    }

    private function valueCell(): ColumnCell
    {
        $column = $this->table->columns()->where('position', 3)->firstOrFail();

        return ColumnCell::query()->where('column_id', $column->id)->firstOrFail();
    }

    #[Test]
    public function add_calculation_inserts_after_position_and_shifts_following_ones(): void
    {
        $this->actingAsBudgetMember();
        $cell = $this->valueCell();
        $existing = $cell->calculations()->create(['name' => 'A', 'value' => 5, 'description' => '', 'position' => 1]);

        $response = $this->postJson(route('project.budget.cell-calculation.add', $cell), ['position' => 0]);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonCount(2, 'calculations');
        $this->assertSame(2, $existing->fresh()->position);
        $this->assertSame(1, CellCalculation::query()->whereKey($response->json('calculation.id'))->value('position'));
    }

    #[Test]
    public function update_cell_calculation_syncs_list_and_sets_cell_value_to_sum(): void
    {
        $this->actingAsBudgetMember();
        $cell = $this->valueCell();
        $kept = $cell->calculations()->create(['name' => 'Alt', 'value' => 1, 'description' => '', 'position' => 1]);
        $dropped = $cell->calculations()->create(['name' => 'Weg', 'value' => 7, 'description' => '', 'position' => 2]);

        $response = $this->patchJson(route('project.budget.cell-calculation.update'), [
            'cell_id' => $cell->id,
            'calculations' => [
                ['id' => $kept->id, 'name' => 'Gage', 'value' => 100, 'description' => '', 'position' => 1],
                ['name' => 'Reise', 'value' => 50, 'position' => 2],
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame('Gage', $kept->fresh()->name);
        $this->assertNull(CellCalculation::query()->find($dropped->id));
        $this->assertSame(2, $cell->calculations()->count());
        $this->assertEquals(150, (float) $cell->fresh()->value);
    }

    #[Test]
    public function outsider_cannot_update_cell_calculation(): void
    {
        $this->actingAs(User::factory()->create());
        $cell = $this->valueCell();

        $this->patchJson(route('project.budget.cell-calculation.update'), [
            'cell_id' => $cell->id,
            'calculations' => [['name' => 'X', 'value' => 999]],
        ])->assertForbidden();

        $this->assertSame(0, $cell->calculations()->count());
    }

    #[Test]
    public function update_cell_source_stores_linked_type_and_returns_json(): void
    {
        $this->actingAsBudgetMember();
        $cell = $this->valueCell();

        $this->patchJson(route('project.budget.cell-source.update'), [
            'cell_id' => $cell->id,
            'linked_type' => 'COST',
            'money_source_id' => null,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('COST', $cell->fresh()->linked_type);
    }

    #[Test]
    public function duplicate_row_copies_cells_without_verified_values(): void
    {
        $this->actingAsBudgetMember();
        $subPosition = $this->subPosition();
        $row = $subPosition->subPositionRows()->firstOrFail();
        $row->cells()->update(['verified_value' => '123']);
        $rowsBefore = $subPosition->subPositionRows()->count();

        $this->post(route('project.budget.sub-position.duplicate.row', $row))->assertOk();

        $this->assertSame($rowsBefore + 1, $subPosition->subPositionRows()->count());
        $copy = $subPosition->subPositionRows()->orderByDesc('id')->first();
        $this->assertSame($row->cells()->count(), $copy->cells()->count());
        $this->assertTrue($copy->cells()->get()->every(fn ($cell) => $cell->verified_value === null));
    }

    #[Test]
    public function duplicate_sub_position_without_rows_creates_unverified_copy_in_same_main_position(): void
    {
        $this->actingAsBudgetMember();
        $subPosition = $this->subPosition();
        $subPosition->update(['name' => 'Technik']);
        $subPosition->subPositionRows()->forceDelete();

        $this->post(route('project.budget.sub-position.duplicate', $subPosition))->assertOk();

        $copy = SubPosition::query()
            ->where('main_position_id', $subPosition->main_position_id)
            ->where('name', 'Technik ' . __('(Copy)'))
            ->sole();
        $this->assertSame('BUDGET_VERIFIED_TYPE_NOT_VERIFIED', $copy->getRawOriginal('is_verified'));
        $this->assertSame(0, SubPositionRow::query()->where('sub_position_id', $copy->id)->count());
    }

    #[Test]
    public function duplicate_sub_position_with_rows_copies_rows_and_cells(): void
    {
        $this->actingAsBudgetMember();
        $subPosition = $this->subPosition();
        $subPosition->update(['name' => 'Licht']);
        $rows = $subPosition->subPositionRows()->count();
        $this->assertGreaterThan(0, $rows);

        // vorher 500: sub_position_rows hat keine Spalte "name"
        $this->post(route('project.budget.sub-position.duplicate', $subPosition))->assertOk();

        $copy = SubPosition::query()->where('name', 'Licht ' . __('(Copy)'))->sole();
        $this->assertSame($rows, SubPositionRow::query()->where('sub_position_id', $copy->id)->count());
    }

    #[Test]
    public function outsider_cannot_duplicate_row(): void
    {
        $this->actingAs(User::factory()->create());
        $subPosition = $this->subPosition();
        $row = $subPosition->subPositionRows()->firstOrFail();
        $rowsBefore = $subPosition->subPositionRows()->count();

        $this->post(route('project.budget.sub-position.duplicate.row', $row))->assertForbidden();

        $this->assertSame($rowsBefore, $subPosition->subPositionRows()->count());
    }
}
