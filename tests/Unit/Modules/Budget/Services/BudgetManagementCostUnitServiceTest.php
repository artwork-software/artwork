<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Services\BudgetManagementCostUnitService;
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
