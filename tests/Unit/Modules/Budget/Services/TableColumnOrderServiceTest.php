<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\TableColumnOrder;
use Artwork\Modules\Budget\Services\TableColumnOrderService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TableColumnOrderServiceTest extends TestCase
{
    private TableColumnOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TableColumnOrderService::class);
    }

    #[Test]
    public function create_persists_table_column_order(): void
    {
        $order = $this->service->create([
            'display_text' => 'My Column',
            'position' => 3,
        ]);

        $this->assertNotNull($order->id);
        $this->assertSame(3, (int) $order->position);
        $this->assertDatabaseHas('table_column_orders', [
            'id' => $order->id,
            'display_text' => 'My Column',
        ]);
    }

    #[Test]
    public function get_all_ordered_by_position_returns_sorted_collection(): void
    {
        TableColumnOrder::factory()->create(['display_text' => 'X', 'position' => 5]);
        TableColumnOrder::factory()->create(['display_text' => 'Y', 'position' => 1]);
        TableColumnOrder::factory()->create(['display_text' => 'Z', 'position' => 3]);

        $result = $this->service->getAllOrderedByPosition();

        $positions = $result->pluck('position')->all();
        $this->assertSame([1, 3, 5], array_values($positions));
    }

    #[Test]
    public function update_table_column_orders_sets_position_index(): void
    {
        $a = TableColumnOrder::factory()->create(['display_text' => 'A', 'position' => 10]);
        $b = TableColumnOrder::factory()->create(['display_text' => 'B', 'position' => 20]);
        $c = TableColumnOrder::factory()->create(['display_text' => 'C', 'position' => 30]);

        $this->service->updateTableColumnOrders([$c->id, $a->id, $b->id]);

        $this->assertDatabaseHas('table_column_orders', ['id' => $c->id, 'position' => 0]);
        $this->assertDatabaseHas('table_column_orders', ['id' => $a->id, 'position' => 1]);
        $this->assertDatabaseHas('table_column_orders', ['id' => $b->id, 'position' => 2]);
    }
}
