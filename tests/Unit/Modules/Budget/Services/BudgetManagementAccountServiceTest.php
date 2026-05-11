<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Services\BudgetManagementAccountService;
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
