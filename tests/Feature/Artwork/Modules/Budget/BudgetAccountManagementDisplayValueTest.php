<?php

namespace Tests\Feature\Artwork\Modules\Budget;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\MainPositionService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class BudgetAccountManagementDisplayValueTest extends TestCase
{
    public function testBudgetTabEnrichesDisplayValueForKtoAndKstWhileKeepingNumbers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create(['is_group' => false]);
        $table = Table::factory()->create(['project_id' => $project->id]);

        /** @var ColumnService $columnService */
        $columnService = $this->app->make(ColumnService::class);
        $ktoColumn = $columnService->createColumnInTable($table, 'KTO', 'A', 'empty', 0);
        $kstColumn = $columnService->createColumnInTable($table, 'KST', 'B', 'empty', 1);
        // 3. Spalte (Beschreibung) – sorgt dafür, dass Budget/Summen-Logik (skip(3)) nicht in KTO/KST hineinrechnet.
        $columnService->createColumnInTable($table, 'Beschreibung', 'C', 'empty', 2);

        /** @var MainPositionService $mainPositionService */
        $mainPositionService = $this->app->make(MainPositionService::class);
        $mainPosition = $mainPositionService->createMainPosition(
            $table,
            BudgetTypeEnum::BUDGET_TYPE_COST,
            'Main',
            0
        );

        $subPosition = SubPosition::create([
            'main_position_id' => $mainPosition->id,
            'name' => 'Sub',
            'position' => 0,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value,
            'is_fixed' => false,
        ]);

        $row = SubPositionRow::create([
            'sub_position_id' => $subPosition->id,
            'position' => 0,
            'order' => 0,
            'commented' => false,
        ]);

        ColumnCell::create([
            'column_id' => $ktoColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => '4000',
            'commented' => false,
        ]);
        ColumnCell::create([
            'column_id' => $kstColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => '100',
            'commented' => false,
        ]);

        // Kontenlisten-Entitäten (Nummer → Titel)
        $account = new BudgetManagementAccount();
        $account->account_number = '4000';
        $account->title = 'Sehr langes Konto für Tests';
        $account->save();

        $costUnit = new BudgetManagementCostUnit();
        $costUnit->cost_unit_number = '100';
        $costUnit->title = 'Sehr lange Kostenstelle für Tests';
        $costUnit->save();

        /** @var BudgetService $budgetService */
        $budgetService = $this->app->make(BudgetService::class);
        $data = $budgetService->getBudgetForProjectTab($project, []);

        $tableFromService = $data['BudgetTab']['budget']['table'];
        $cellKto = $tableFromService->mainPositions->first()
            ->subPositions->first()
            ->subPositionRows->first()
            ->cells->firstWhere('column_id', $ktoColumn->id);
        $cellKst = $tableFromService->mainPositions->first()
            ->subPositions->first()
            ->subPositionRows->first()
            ->cells->firstWhere('column_id', $kstColumn->id);

        $this->assertSame('4000', $cellKto->value);
        $this->assertSame('Sehr langes Konto für Tests', $cellKto->display_value);

        $this->assertSame('100', $cellKst->value);
        $this->assertSame('Sehr lange Kostenstelle für Tests', $cellKst->display_value);
    }

    public function testProjectGroupMatchingStillWorksWithNumericKtoKstValues(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $groupProject = Project::factory()->create(['is_group' => true]);
        $subProject = Project::factory()->create(['is_group' => false]);

        // Unterprojekt dem Gruppenprojekt zuordnen
        $groupProject->projectsOfGroup()->attach($subProject->id);

        /** @var ColumnService $columnService */
        $columnService = $this->app->make(ColumnService::class);
        /** @var MainPositionService $mainPositionService */
        $mainPositionService = $this->app->make(MainPositionService::class);

        // Group-Projekt Budget
        $groupTable = Table::factory()->create(['project_id' => $groupProject->id]);
        $groupKto = $columnService->createColumnInTable($groupTable, 'KTO', 'A', 'empty', 0);
        $groupKst = $columnService->createColumnInTable($groupTable, 'KST', 'B', 'empty', 1);
        $columnService->createColumnInTable($groupTable, 'Beschreibung', 'C', 'empty', 2);
        $subprojectsColumn = $columnService->createColumnInTable($groupTable, 'Unterprojekte', '-', 'subprojects_column_for_group', 100);
        $groupMP = $mainPositionService->createMainPosition($groupTable, BudgetTypeEnum::BUDGET_TYPE_COST, 'Main', 0);
        $groupSP = SubPosition::create([
            'main_position_id' => $groupMP->id,
            'name' => 'Sub',
            'position' => 0,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value,
            'is_fixed' => false,
        ]);
        $groupRow = SubPositionRow::create([
            'sub_position_id' => $groupSP->id,
            'position' => 0,
            'order' => 0,
            'commented' => false,
        ]);
        ColumnCell::create([
            'column_id' => $groupKto->id,
            'sub_position_row_id' => $groupRow->id,
            'value' => '4000',
            'commented' => false,
        ]);
        ColumnCell::create([
            'column_id' => $groupKst->id,
            'sub_position_row_id' => $groupRow->id,
            'value' => '100',
            'commented' => false,
        ]);

        // Zelle für Unterprojekte-Spalte (wird serverseitig in der Response mit Summe angereichert)
        ColumnCell::create([
            'column_id' => $subprojectsColumn->id,
            'sub_position_row_id' => $groupRow->id,
            'value' => '0',
            'commented' => false,
        ]);

        // Sub-Projekt Budget
        $subTable = Table::factory()->create(['project_id' => $subProject->id]);
        $subKto = $columnService->createColumnInTable($subTable, 'KTO', 'A', 'empty', 0);
        $subKst = $columnService->createColumnInTable($subTable, 'KST', 'B', 'empty', 1);
        $columnService->createColumnInTable($subTable, 'Beschreibung', 'C', 'empty', 2);
        $relevant = $columnService->createColumnInTable($subTable, 'Relevant', 'D', 'empty', 3, null, null, true);

        $subMP = $mainPositionService->createMainPosition($subTable, BudgetTypeEnum::BUDGET_TYPE_COST, 'Main', 0);
        $subSP = SubPosition::create([
            'main_position_id' => $subMP->id,
            'name' => 'Sub',
            'position' => 0,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value,
            'is_fixed' => false,
        ]);
        $subRow = SubPositionRow::create([
            'sub_position_id' => $subSP->id,
            'position' => 0,
            'order' => 0,
            'commented' => false,
        ]);
        ColumnCell::create([
            'column_id' => $subKto->id,
            'sub_position_row_id' => $subRow->id,
            'value' => '4000',
            'commented' => false,
        ]);
        ColumnCell::create([
            'column_id' => $subKst->id,
            'sub_position_row_id' => $subRow->id,
            'value' => '100',
            'commented' => false,
        ]);
        $relevantCell = ColumnCell::create([
            'column_id' => $relevant->id,
            'sub_position_row_id' => $subRow->id,
            'value' => '123,45',
            'commented' => false,
        ]);

        /** @var BudgetService $budgetService */
        $budgetService = $this->app->make(BudgetService::class);
        $data = $budgetService->getBudgetForProjectTab($groupProject, []);

        $grouped = $data['BudgetTab']['projectGroupRelevantBudgetData'];
        $this->assertArrayHasKey(BudgetTypeEnum::BUDGET_TYPE_COST->value, $grouped);
        $this->assertNotEmpty($grouped[BudgetTypeEnum::BUDGET_TYPE_COST->value]);

        $dto = $grouped[BudgetTypeEnum::BUDGET_TYPE_COST->value][0];
        $this->assertSame('4000', $dto->value1);
        $this->assertSame('100', $dto->value2);
        $this->assertSame($subProject->id, $dto->subProjectId);
        $this->assertSame($relevant->id, $dto->relevantColumnId);
        $this->assertSame($relevantCell->id, $dto->cellId);
        $this->assertSame('123,45', $dto->value);

        // Und zusätzlich: der Wert wird in der Unterprojekte-Spalte der Gruppen-Zeile angezeigt
        $tableFromService = $data['BudgetTab']['budget']['table'];
        $groupRowFromService = $tableFromService->mainPositions->first()
            ->subPositions->first()
            ->subPositionRows->first();

        $subprojectsCell = $groupRowFromService->cells->firstWhere('column_id', $subprojectsColumn->id);
        $this->assertNotNull($subprojectsCell);
        $this->assertSame('123,45', $subprojectsCell->value);
    }
}
