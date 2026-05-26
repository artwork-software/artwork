<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TableServiceTest extends TestCase
{
    private TableService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TableService::class);
    }

    #[Test]
    public function get_all_returns_all_tables(): void
    {
        Table::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    #[Test]
    public function create_table_in_project_persists_record(): void
    {
        $project = Project::factory()->create();

        $table = $this->service->createTableInProject($project, 'Test Budget Table', false);

        $this->assertNotNull($table->id);
        $this->assertSame('Test Budget Table', $table->name);
        $this->assertSame($project->id, $table->project_id);
        $this->assertFalse((bool) $table->is_template);
        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'name' => 'Test Budget Table',
            'project_id' => $project->id,
        ]);
    }

    #[Test]
    public function create_template_table_in_project_sets_template_flag(): void
    {
        $project = Project::factory()->create();

        $table = $this->service->createTableInProject($project, 'Template', true);

        $this->assertTrue((bool) $table->is_template);
    }
}
