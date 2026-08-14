<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetManagementCostUnitService;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetManagementCostUnitServiceTest extends TestCase
{
    private BudgetManagementCostUnitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetManagementCostUnitService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        BudgetManagementCostUnit::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    #[Test]
    public function get_all_trashed_returns_only_deleted(): void
    {
        $kept = BudgetManagementCostUnit::factory()->create();
        $deleted = BudgetManagementCostUnit::factory()->create();
        $deleted->delete();

        $result = $this->service->getAllTrashed();

        $ids = $result->pluck('id')->all();
        $this->assertContains($deleted->id, $ids);
        $this->assertNotContains($kept->id, $ids);
    }

    #[Test]
    public function soft_delete_zeroes_kst_cells_by_column_position_even_if_id_order_is_swapped(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);

        // Globaler Sage-Spalten-Swap: Spalte mit kleinerer id steht per position an zweiter Stelle
        $kstColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $ktoColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 0]);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        // Zeile ohne Zellen darf den Durchlauf nicht abbrechen
        SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        $costUnit = BudgetManagementCostUnit::factory()->create();

        $ktoCell = ColumnCell::factory()->create([
            'column_id' => $ktoColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => $costUnit->cost_unit_number,
        ]);
        $kstCell = ColumnCell::factory()->create([
            'column_id' => $kstColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => $costUnit->cost_unit_number,
        ]);

        // Projekt ohne Budgettabelle darf den Durchlauf nicht abbrechen
        Project::factory()->create();

        $this->service->softDelete($costUnit, app(ProjectService::class), app(ColumnCellService::class));

        $this->assertSame('00000', $kstCell->refresh()->value);
        $this->assertSame($costUnit->cost_unit_number, $ktoCell->refresh()->value);
        $this->assertSoftDeleted('budget_management_cost_units', ['id' => $costUnit->id]);
    }

    #[Test]
    public function search_by_request_filters_by_term(): void
    {
        BudgetManagementCostUnit::factory()->create([
            'cost_unit_number' => '5555',
            'title' => 'Theater',
        ]);
        BudgetManagementCostUnit::factory()->create([
            'cost_unit_number' => '4444',
            'title' => 'Workshop',
        ]);

        $request = Request::create('/?search=Theater', 'GET');

        $result = $this->service->searchByRequest($request);

        $titles = $result->pluck('title')->all();
        $this->assertContains('Theater', $titles);
        $this->assertNotContains('Workshop', $titles);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_cost_unit(): void
    {
        $unit = BudgetManagementCostUnit::factory()->create();
        $unit->delete();

        $this->service->restore($unit);

        $this->assertDatabaseHas('budget_management_cost_units', [
            'id' => $unit->id,
            'deleted_at' => null,
        ]);
    }
}
