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
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\CreateAdminUser;
use Tests\Feature\FeatureTestCase;

/**
 * FIN-03: KTO/KST/Beschreibung werden im Excel-Export als Text ausgegeben
 * (führende Nullen bleiben), Wertspalten weiterhin als Zahl.
 */
final class BudgetExportTextColumnsTest extends FeatureTestCase
{
    use CreateAdminUser;

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
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*04000 – Personalkosten\s*<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*0815 – Bühne\s*<\/td>/', $html);
        // Nummern ohne hinterlegtes Konto bleiben als Nummer stehen (8000 hat kein Konto)
        $this->assertMatchesRegularExpression('/<td\s+data-type="s"\s*>\s*8000\s*<\/td>/', $html);
    }

    #[Test]
    public function export_route_delivers_number_and_name(): void
    {
        Excel::fake();
        $this->setAccountManagement(true);
        BudgetManagementAccount::factory()->create(['account_number' => '04000', 'title' => 'Personalkosten']);
        [$table] = $this->createBudget();

        $this->actingAs($this->adminUser())
            ->get(route('projects.export.budget', ['project' => $table->project->id]))
            ->assertOk();

        Excel::matchByRegex();
        Excel::assertDownloaded('/_budget_stand_.*\.xlsx$/', function (BudgetExport $export): bool {
            return in_array('04000 – Personalkosten', $export->getData()['cellDisplayValues'], true);
        });
    }

    #[Test]
    public function generated_formulas_stay_formulas_while_user_text_stays_text(): void
    {
        $this->setAccountManagement(false);
        [$table, $columns, $row] = $this->createBudget();

        // Summenspalte (Plan + Plan) → createFormula in den Positionszeilen
        $sumColumn = Column::factory()->create([
            'table_id' => $table->id,
            'name' => 'Summe',
            'position' => 4,
            'type' => 'sum',
            'linked_first_column' => $columns[3]->id,
            'linked_second_column' => $columns[3]->id,
        ]);
        foreach (SubPositionRow::all() as $subPositionRow) {
            ColumnCell::factory()->create([
                'column_id' => $sumColumn->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => '0',
            ]);
        }
        MainPosition::query()
            ->whereKey(SubPosition::query()->whereKey($row->sub_position_id)->value('main_position_id'))
            ->update(['name' => '=HYPERLINK("http://evil.test","x")']);

        // Echte xlsx-Datei erzeugen: nur so greift der globale SafeValueBinder wie im Download
        $path = sys_get_temp_dir() . '/' . uniqid('budget', true) . '.xlsx';
        file_put_contents($path, Excel::raw(new BudgetExport($table->project), \Maatwebsite\Excel\Excel::XLSX));
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);

        $cellsByValue = [];
        foreach ($sheet->getRowIterator() as $sheetRow) {
            foreach ($sheetRow->getCellIterator() as $cell) {
                $value = (string) $cell->getValue();
                if ($value !== '') {
                    $cellsByValue[$value][] = $cell->getDataType();
                }
            }
        }

        $formulaTypes = array_filter(
            $cellsByValue,
            fn(string $value): bool => str_starts_with($value, '=SUM(') || preg_match('/^=[A-Z]+\d+[+-]/', $value) === 1,
            ARRAY_FILTER_USE_KEY
        );
        $this->assertNotEmpty($formulaTypes);
        foreach ($formulaTypes as $value => $types) {
            $this->assertSame([DataType::TYPE_FORMULA], array_unique($types), $value);
        }

        $this->assertSame([DataType::TYPE_STRING], $cellsByValue['=HYPERLINK("http://evil.test","x")']);
        $this->assertSame([DataType::TYPE_STRING], $cellsByValue['04000']);
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
