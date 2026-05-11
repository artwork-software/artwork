<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnService;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ColumnServiceTest extends TestCase
{
    private ColumnService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->service = app(ColumnService::class);
    }

    #[Test]
    public function create_column_in_table_persists_record(): void
    {
        $table = Table::factory()->create();

        $column = $this->service->createColumnInTable(
            $table,
            'My Column',
            'A',
            'empty',
            0
        );

        $this->assertNotNull($column->id);
        $this->assertSame('My Column', $column->name);
        $this->assertSame('A', $column->subName);
        $this->assertSame('empty', $column->type);
        $this->assertSame(0, $column->position);
        $this->assertSame($table->id, $column->table_id);
    }

    #[Test]
    public function create_column_passes_optional_linked_columns(): void
    {
        $table = Table::factory()->create();

        $column = $this->service->createColumnInTable(
            $table,
            'Diff',
            'B',
            'difference',
            1,
            10,
            11,
            true,
        );

        $this->assertSame(10, $column->linked_first_column);
        $this->assertSame(11, $column->linked_second_column);
        $this->assertTrue((bool) $column->relevant_for_project_groups);
    }

    #[Test]
    public function get_name_from_number_returns_alpha_label(): void
    {
        $this->assertSame('A', $this->service->getNameFromNumber(1));
        $this->assertSame('B', $this->service->getNameFromNumber(2));
        $this->assertSame('Z', $this->service->getNameFromNumber(26));
        $this->assertSame('AA', $this->service->getNameFromNumber(27));
        $this->assertSame('AB', $this->service->getNameFromNumber(28));
    }

    #[Test]
    public function set_column_sub_name_renumbers_columns_with_subname(): void
    {
        $table = Table::factory()->create();
        Column::factory()->create(['table_id' => $table->id, 'subName' => 'x', 'position' => 0, 'type' => 'empty']);
        Column::factory()->create(['table_id' => $table->id, 'subName' => 'y', 'position' => 1, 'type' => 'empty']);
        Column::factory()->create(['table_id' => $table->id, 'subName' => '', 'position' => 2, 'type' => 'empty']);

        $this->service->setColumnSubName($table->id);

        $columns = $table->columns()->orderBy('position')->get();
        $this->assertSame('A', $columns[0]->subName);
        $this->assertSame('B', $columns[1]->subName);
        $this->assertSame('', $columns[2]->subName);
    }

    #[Test]
    public function update_or_fail_updates_attributes(): void
    {
        $table = Table::factory()->create();
        $column = Column::factory()->create(['table_id' => $table->id, 'name' => 'Old']);

        $this->service->updateOrFail($column, ['name' => 'Renamed']);

        $this->assertDatabaseHas('columns', [
            'id' => $column->id,
            'name' => 'Renamed',
        ]);
    }
}
