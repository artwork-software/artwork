<?php

namespace Tests\Unit\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CraftInventoryItemCellServiceTest extends TestCase
{
    private CraftInventoryItemCellService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CraftInventoryItemCellService::class);
    }

    #[Test]
    public function get_name_for_scheduling_returns_value_from_first_column_cell(): void
    {
        $cells = new Collection([
            new CraftInventoryItemCell(['crafts_inventory_column_id' => 2, 'cell_value' => '10']),
            new CraftInventoryItemCell(['crafts_inventory_column_id' => 1, 'cell_value' => 'Item Name']),
        ]);

        $this->assertSame('Item Name', $this->service->getNameForSchedulingFromCells($cells));
    }

    #[Test]
    public function get_name_for_scheduling_returns_empty_when_no_column_one_cell(): void
    {
        $cells = new Collection([
            new CraftInventoryItemCell(['crafts_inventory_column_id' => 2, 'cell_value' => '10']),
        ]);

        $this->assertSame('', $this->service->getNameForSchedulingFromCells($cells));
    }

    #[Test]
    public function get_item_count_for_scheduling_returns_first_numeric_cell(): void
    {
        $cells = new Collection([
            new CraftInventoryItemCell(['crafts_inventory_column_id' => 1, 'cell_value' => 'Name']),
            new CraftInventoryItemCell(['crafts_inventory_column_id' => 2, 'cell_value' => '42']),
        ]);

        $this->assertSame(42, $this->service->getItemCountForSchedulingFromCells($cells));
    }

    #[Test]
    public function get_item_count_returns_zero_when_no_numeric_cell(): void
    {
        $cells = new Collection([
            new CraftInventoryItemCell(['crafts_inventory_column_id' => 1, 'cell_value' => 'Name']),
        ]);

        $this->assertSame(0, $this->service->getItemCountForSchedulingFromCells($cells));
    }
}
