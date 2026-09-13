<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Exports\BudgetExport;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * FIN-03: KTO/KST/Beschreibung werden im Excel-Export als Text ausgegeben
 * (führende Nullen bleiben), Wertspalten weiterhin als Zahl.
 */
final class BudgetExportTextColumnsTest extends FeatureTestCase
{
    /**
     * @return array{Table, array<int, Column>, SubPositionRow}
     */
    private function createBudget(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        $columns = [];
        foreach (['KTO', 'KST', 'Beschreibung', 'Plan'] as $position => $name) {
            $columns[$position] = Column::factory()->create([
                'table_id' => $table->id,
                'name' => $name,
                'position' => $position,
                'type' => 'empty',
            ]);
        }

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id, 'position' => 1]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id, 'position' => 1, 'order' => 1]);

        foreach ([0 => '04000', 1 => '0815', 2 => 'Technik Personal', 3 => '1250,50'] as $position => $value) {
            ColumnCell::factory()->create([
                'column_id' => $columns[$position]->id,
                'sub_position_row_id' => $row->id,
                'value' => $value,
            ]);
        }

        // Echte Budgets haben immer Kosten UND Erloese; die SUM-Zeile subtrahiert beide.
        $earningPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'position' => 1,
            'type' => 'BUDGET_TYPE_EARNING',
        ]);
        $earningSubPosition = SubPosition::factory()->create(['main_position_id' => $earningPosition->id, 'position' => 1]);
        $earningRow = SubPositionRow::factory()->create(['sub_position_id' => $earningSubPosition->id, 'position' => 1, 'order' => 1]);
        foreach ([0 => '8000', 1 => '0815', 2 => 'Ticketerloese', 3 => '500'] as $position => $value) {
            ColumnCell::factory()->create([
                'column_id' => $columns[$position]->id,
                'sub_position_row_id' => $earningRow->id,
                'value' => $value,
            ]);
        }

        return [$table->refresh(), $columns, $row];
    }

    private function setAccountManagement(bool $active): void
    {
        $settings = app(GeneralSettings::class);
        $settings->budget_account_management_global = $active;
        $settings->save();
    }

    private function renderExport(Table $table): string
    {
        $export = new BudgetExport($table->project);

        return view('exports.projectBudget', ['data' => $export->getData()])->render();
    }

    #[Test]
    public function kto_kst_and_description_are_exported_as_text_with_leading_zeros(): void
    {
        $this->setAccountManagement(false);
        [$table] = $this->createBudget();

        $html = $this->renderExport($table);

        // Textspalten explizit als String (data-type="s"), führende Null bleibt erhalten
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*04000\s*<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*0815\s*<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*Technik Personal\s*<\/td>/', $html);
        $this->assertStringNotContainsString('>4000<', $html);
        // Wertspalte bleibt numerisch ((float) "1250,50" → 1250)
        $this->assertMatchesRegularExpression('/<td\s*>\s*1250\s*<\/td>/', $html);
    }

    #[Test]
    public function active_account_management_exports_number_and_name(): void
    {
        $this->setAccountManagement(true);
        BudgetManagementAccount::factory()->create(['account_number' => '04000', 'title' => 'Personalkosten']);
        BudgetManagementCostUnit::factory()->create(['cost_unit_number' => '0815', 'title' => 'Bühne']);
        [$table] = $this->createBudget();

        $export = new BudgetExport($table->project);
        $data = $export->getData();
        $this->assertContains('04000 – Personalkosten', $data['cellDisplayValues']);
        $this->assertContains('0815 – Bühne', $data['cellDisplayValues']);

        $html = view('exports.projectBudget', ['data' => $data])->render();
        $this->assertStringContainsString('04000 – Personalkosten', $html);
        $this->assertStringContainsString('0815 – Bühne', $html);
    }

    #[Test]
    public function inactive_account_management_exports_raw_numbers(): void
    {
        $this->setAccountManagement(false);
        BudgetManagementAccount::factory()->create(['account_number' => '04000', 'title' => 'Personalkosten']);
        [$table] = $this->createBudget();

        $html = $this->renderExport($table);

        $this->assertStringNotContainsString('Personalkosten', $html);
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*04000\s*<\/td>/', $html);
    }
}
