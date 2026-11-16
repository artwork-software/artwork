<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Papierkorb der Konten und Kostenstellen (Budget-Einstellungen): Wiederherstellen,
 * endgültig Löschen einzeln und gesammelt. Endgültiges Löschen setzt die KTO-/KST-Zellen
 * aller Projekte, die noch auf die Nummer zeigen, auf "00000".
 */
final class BudgetManagementTrashTest extends FeatureTestCase
{
    /**
     * Projektbudget mit KTO- (Position 0), KST- (Position 1) und Wertspalte; die Spalten-Ids
     * laufen absichtlich gegen die Positionen, damit die Zuordnung nach position geprüft wird.
     *
     * @return array{kto: ColumnCell, kst: ColumnCell, value: ColumnCell}
     */
    private function createProjectRow(string $kto, string $kst): array
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        $valueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 2]);
        $kstColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $ktoColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 0]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        return [
            'kto' => ColumnCell::factory()->create([
                'column_id' => $ktoColumn->id,
                'sub_position_row_id' => $row->id,
                'value' => $kto,
            ]),
            'kst' => ColumnCell::factory()->create([
                'column_id' => $kstColumn->id,
                'sub_position_row_id' => $row->id,
                'value' => $kst,
            ]),
            'value' => ColumnCell::factory()->create([
                'column_id' => $valueColumn->id,
                'sub_position_row_id' => $row->id,
                'value' => $kto,
            ]),
        ];
    }

    #[Test]
    public function force_deleting_a_trashed_account_resets_matching_kto_cells_and_removes_it(): void
    {
        $this->actingAsAdmin();
        $account = BudgetManagementAccount::factory()->create(['account_number' => '47110']);
        $account->delete();
        $matching = $this->createProjectRow('47110', '47110');
        $other = $this->createProjectRow('99999', '47110');

        $this->delete(route('budget-settings.account-management.trash-accounts.forceDelete', $account->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('budget_management_accounts', ['id' => $account->id]);
        $this->assertSame('00000', $matching['kto']->fresh()->value);
        // Nur die KTO-Spalte wird zurückgesetzt, nicht KST- oder Wertspalte mit gleicher Zeichenfolge
        $this->assertSame('47110', $matching['kst']->fresh()->value);
        $this->assertSame('47110', $matching['value']->fresh()->value);
        $this->assertSame('99999', $other['kto']->fresh()->value);
    }

    #[Test]
    public function force_delete_all_accounts_only_removes_trashed_ones(): void
    {
        $this->actingAsAdmin();
        $trashedFirst = BudgetManagementAccount::factory()->create();
        $trashedSecond = BudgetManagementAccount::factory()->create();
        $active = BudgetManagementAccount::factory()->create();
        $trashedFirst->delete();
        $trashedSecond->delete();
        $cells = $this->createProjectRow($trashedFirst->account_number, '1');

        $this->delete(route('budget-settings.account-management.trash-accounts.forceDeleteAll'))
            ->assertRedirect(route('budget-settings.account-management.trash-accounts'));

        $this->assertDatabaseMissing('budget_management_accounts', ['id' => $trashedFirst->id]);
        $this->assertDatabaseMissing('budget_management_accounts', ['id' => $trashedSecond->id]);
        $this->assertNotSoftDeleted($active);
        $this->assertSame('00000', $cells['kto']->fresh()->value);
    }

    #[Test]
    public function force_delete_all_accounts_with_empty_trash_is_a_no_op(): void
    {
        $this->actingAsAdmin();
        $active = BudgetManagementAccount::factory()->create();

        $this->delete(route('budget-settings.account-management.trash-accounts.forceDeleteAll'))
            ->assertRedirect(route('budget-settings.account-management.trash-accounts'));

        $this->assertNotSoftDeleted($active);
    }

    #[Test]
    public function force_delete_account_ignores_projects_without_budget_table(): void
    {
        $this->actingAsAdmin();
        Project::factory()->create();
        $account = BudgetManagementAccount::factory()->create();
        $account->delete();

        $this->delete(route('budget-settings.account-management.trash-accounts.forceDelete', $account->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('budget_management_accounts', ['id' => $account->id]);
    }

    #[Test]
    public function restoring_a_trashed_cost_unit_brings_it_back(): void
    {
        $this->actingAsAdmin();
        $costUnit = BudgetManagementCostUnit::factory()->create();
        $costUnit->delete();

        $this->patch(route('budget-settings.account-management.trash-cost-units.restore', $costUnit->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted($costUnit);
    }

    #[Test]
    public function restoring_an_unknown_cost_unit_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('budget-settings.account-management.trash-cost-units.restore', 999999999))
            ->assertNotFound();
    }

    #[Test]
    public function force_deleting_a_trashed_cost_unit_resets_matching_kst_cells_only(): void
    {
        $this->actingAsAdmin();
        $costUnit = BudgetManagementCostUnit::factory()->create(['cost_unit_number' => '30100']);
        $costUnit->delete();
        $cells = $this->createProjectRow('30100', '30100');

        $this->delete(route('budget-settings.account-management.trash-cost-units.forceDelete', $costUnit->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('budget_management_cost_units', ['id' => $costUnit->id]);
        $this->assertSame('00000', $cells['kst']->fresh()->value);
        $this->assertSame('30100', $cells['kto']->fresh()->value);
        $this->assertSame('30100', $cells['value']->fresh()->value);
    }

    #[Test]
    public function force_delete_all_cost_units_only_removes_trashed_ones(): void
    {
        $this->actingAsAdmin();
        $trashed = BudgetManagementCostUnit::factory()->create();
        $active = BudgetManagementCostUnit::factory()->create();
        $trashed->delete();
        $cells = $this->createProjectRow('1', $trashed->cost_unit_number);

        $this->delete(route('budget-settings.account-management.trash-cost-units.forceDeleteAll'))
            ->assertRedirect(route('budget-settings.account-management.trash-cost-units'));

        $this->assertDatabaseMissing('budget_management_cost_units', ['id' => $trashed->id]);
        $this->assertNotSoftDeleted($active);
        $this->assertSame('00000', $cells['kst']->fresh()->value);
    }

    #[Test]
    public function force_delete_cost_unit_skips_tables_with_only_one_column(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        Column::factory()->create(['table_id' => $table->id, 'position' => 0]);
        $costUnit = BudgetManagementCostUnit::factory()->create();
        $costUnit->delete();

        $this->delete(route('budget-settings.account-management.trash-cost-units.forceDelete', $costUnit->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('budget_management_cost_units', ['id' => $costUnit->id]);
    }
}
