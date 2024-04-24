<?php

namespace Tests\Unit\App\Exports;

use Artwork\Modules\Budget\Exports\BudgetExport;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Tests\TestCase;

class BudgetExportTest extends TestCase
{
    public function testView(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory(['project_id' => $project->id])->create();
        $projectBudgetExport = new BudgetExport($project);
        $view = $projectBudgetExport->view();

        $this->assertEquals('exports.projectBudget', $view->name());
        $this->assertArrayHasKey('data', $view->getData());

        $project->delete();
    }

    public function testGetData(): void
    {
        $project = Project::factory()->create();
        $table = Table::factory(['project_id' => $project->id])->create();
        $projectBudgetExport = new BudgetExport($project);

        $data = $projectBudgetExport->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('budgetTable', $data);
        $this->assertArrayHasKey('budgetTypeCost', $data);
        $this->assertArrayHasKey('budgetTypeEarning', $data);

        $project->delete();
    }

    public function testStyles(): void
    {
        $project = Project::factory()->create();
        $projectBudgetExport = new BudgetExport($project);

        $styles = $projectBudgetExport->styles(new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet());

        $this->assertIsArray($styles);
        $this->assertArrayHasKey(1, $styles);
        $this->assertArrayHasKey('font', $styles[1]);
        $this->assertArrayHasKey('bold', $styles[1]['font']);
        $this->assertTrue($styles[1]['font']['bold']);

        $project->delete();
    }
}
