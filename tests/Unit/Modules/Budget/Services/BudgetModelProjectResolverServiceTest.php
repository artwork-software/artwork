<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetModelProjectResolverService;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetModelProjectResolverServiceTest extends TestCase
{
    private BudgetModelProjectResolverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetModelProjectResolverService::class);
    }

    #[Test]
    public function resolve_project_id_for_table(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id]);

        $this->assertSame($project->id, $this->service->resolveProjectId($table));
    }

    #[Test]
    public function resolve_project_id_for_column(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id]);
        $column = Column::factory()->create(['table_id' => $table->id]);

        $this->assertSame($project->id, $this->service->resolveProjectId($column));
    }

    #[Test]
    public function resolve_project_id_for_main_position(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);

        $this->assertSame($project->id, $this->service->resolveProjectId($mainPosition));
    }

    #[Test]
    public function resolve_project_id_for_sub_position(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);

        $this->assertSame($project->id, $this->service->resolveProjectId($subPosition));
    }

    #[Test]
    public function resolve_project_id_for_column_cell(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $column = Column::factory()->create(['table_id' => $table->id]);
        $cell = ColumnCell::factory()->create([
            'sub_position_row_id' => $row->id,
            'column_id' => $column->id,
        ]);

        $this->assertSame($project->id, $this->service->resolveProjectId($cell));
    }
}
