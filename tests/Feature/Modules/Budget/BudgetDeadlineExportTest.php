<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetDeadlineExportTest extends FeatureTestCase
{
    private function exportRoute(array $query): string
    {
        return route('projects.export.budgetByBudgetDeadline', array_merge([
            'startBudgetDeadline' => '2026-01-01',
            'endBudgetDeadline' => '2026-12-31',
        ], $query));
    }

    #[Test]
    public function aggregated_export_rejects_unknown_columns(): void
    {
        $this->actingAsAdmin();

        $this->get($this->exportRoute(['type' => 0, 'desiredColumns' => ['bogus_column']]))
            ->assertSessionHasErrors('desiredColumns.0');
    }

    #[Test]
    public function detailed_export_rejects_unknown_columns(): void
    {
        $this->actingAsAdmin();

        $this->get($this->exportRoute(['type' => 1, 'desiredColumns' => ['bogus_column']]))
            ->assertSessionHasErrors('desiredColumns.0');
    }

    #[Test]
    public function aggregated_export_accepts_a_column_subset(): void
    {
        $this->actingAsAdmin();

        $this->get($this->exportRoute([
            'type' => 0,
            'desiredColumns' => ['project_name', 'forecast_costs'],
        ]))->assertOk();
    }

    #[Test]
    public function detailed_export_accepts_a_column_subset(): void
    {
        $this->actingAsAdmin();

        $this->get($this->exportRoute([
            'type' => 1,
            'desiredColumns' => ['project_name', 'forecast_costs', 'source'],
        ]))->assertOk();
    }

    #[Test]
    public function aggregated_export_works_without_column_selection(): void
    {
        $this->actingAsAdmin();

        $project = Project::factory()->create(['budget_deadline' => '2026-06-15']);
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }
        Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);

        $this->get($this->exportRoute(['type' => 0]))->assertOk();
    }
}
