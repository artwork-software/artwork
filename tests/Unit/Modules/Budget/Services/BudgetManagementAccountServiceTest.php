<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetManagementAccountService;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetManagementAccountServiceTest extends TestCase
{
    private BudgetManagementAccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetManagementAccountService::class);
    }

    #[Test]
    public function get_all_ordered_by_revenue_returns_collection(): void
    {
        BudgetManagementAccount::factory()->count(2)->create(['is_account_for_revenue' => false]);
        BudgetManagementAccount::factory()->create(['is_account_for_revenue' => true]);

        $result = $this->service->getAllOrderedByIsAccountForRevenue();

        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    #[Test]
    public function get_all_trashed_returns_only_deleted(): void
    {
        $kept = BudgetManagementAccount::factory()->create();
        $deleted = BudgetManagementAccount::factory()->create();
        $deleted->delete();

        $result = $this->service->getAllTrashed();

        $ids = $result->pluck('id')->all();
        $this->assertContains($deleted->id, $ids);
        $this->assertNotContains($kept->id, $ids);
    }

    #[Test]
    public function search_by_request_filters_by_search_term(): void
    {
        BudgetManagementAccount::factory()->create([
            'account_number' => '12345',
            'title' => 'Personalkosten',
            'is_account_for_revenue' => false,
        ]);
        BudgetManagementAccount::factory()->create([
            'account_number' => '99999',
            'title' => 'Sonstiges',
            'is_account_for_revenue' => false,
        ]);

        $request = Request::create('/?search=Personal&is_account_for_revenue=0', 'GET');

        $result = $this->service->searchByRequest($request);

        $titles = $result->pluck('title')->all();
        $this->assertContains('Personalkosten', $titles);
        $this->assertNotContains('Sonstiges', $titles);
    }

    #[Test]
    public function search_matches_second_word_of_title(): void
    {
        // "Technik Personal" muss über "Personal" gefunden werden (Substring, nicht Präfix).
        BudgetManagementAccount::factory()->create([
            'account_number' => '04000',
            'title' => 'Technik Personal',
            'is_account_for_revenue' => false,
        ]);
        BudgetManagementAccount::factory()->create([
            'account_number' => '04100',
            'title' => 'Technik Material',
            'is_account_for_revenue' => false,
        ]);

        $result = $this->service->searchByRequest(
            Request::create('/?search=Personal&is_account_for_revenue=0', 'GET')
        );

        $titles = $result->pluck('title')->all();
        $this->assertContains('Technik Personal', $titles);
        $this->assertNotContains('Technik Material', $titles);
    }

    #[Test]
    public function search_is_limited_to_fifty_results(): void
    {
        BudgetManagementAccount::factory()->count(55)->create([
            'title' => 'Massenkonto',
            'is_account_for_revenue' => false,
        ]);

        $result = $this->service->searchByRequest(
            Request::create('/?search=Massenkonto&is_account_for_revenue=0', 'GET')
        );

        $this->assertCount(50, $result);
    }

    #[Test]
    public function soft_delete_zeroes_kto_cells_by_column_position_even_if_id_order_is_swapped(): void
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

        $account = BudgetManagementAccount::factory()->create();

        $ktoCell = ColumnCell::factory()->create([
            'column_id' => $ktoColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => $account->account_number,
        ]);
        $kstCell = ColumnCell::factory()->create([
            'column_id' => $kstColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => $account->account_number,
        ]);

        // Projekt ohne Budgettabelle darf den Durchlauf nicht abbrechen
        Project::factory()->create();

        $this->service->softDelete($account, app(ProjectService::class), app(ColumnCellService::class));

        $this->assertSame('00000', $ktoCell->refresh()->value);
        $this->assertSame($account->account_number, $kstCell->refresh()->value);
        $this->assertSoftDeleted('budget_management_accounts', ['id' => $account->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_account(): void
    {
        $account = BudgetManagementAccount::factory()->create();
        $account->delete();

        $this->service->restore($account);

        $this->assertDatabaseHas('budget_management_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }
}
