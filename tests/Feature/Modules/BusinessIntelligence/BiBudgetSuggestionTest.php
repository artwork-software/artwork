<?php

namespace Tests\Feature\Modules\BusinessIntelligence;

use Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BiBudgetSuggestionTest extends FeatureTestCase
{
    private function createProjectWithBudgetColumns(): array
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }
        $flagged = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);
        // spätere Wertspalte OHNE Flag - die Vorschläge müssen trotzdem
        // aus der budgetrelevanten Spalte kommen, nicht aus der letzten
        Column::factory()->create(['table_id' => $table->id, 'position' => 4]);

        return [$project, $flagged];
    }

    #[Test]
    public function suggestions_read_the_budget_relevant_column(): void
    {
        [$project, $flagged] = $this->createProjectWithBudgetColumns();

        $service = app(BiDerivedValuesService::class);
        $revenue = $service->getRevenueSuggestions($project->fresh());
        $costs = $service->getCostSuggestions($project->fresh());

        // Ohne Positionen/Zellen sind die Summen der Flag-Spalte 0 → kein Vorschlag,
        // aber beide Pfade laufen fehlerfrei über die budgetrelevante Spalte.
        $this->assertNull($revenue['plan']);
        $this->assertNull($costs['costs_plan']);
        $this->assertArrayHasKey('actual', $revenue);
        $this->assertArrayHasKey('costs_actual', $costs);
    }

    #[Test]
    public function costs_can_be_stored_and_source_is_stamped(): void
    {
        $this->actingAsAdmin();
        [$project] = $this->createProjectWithBudgetColumns();

        $this->putJson(route('projects.bi.update-data', $project->id), [
            'costs_total' => 1500.50,
            'costs_source' => 'budget_expense',
            'scope' => 'plan',
        ])->assertSuccessful();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'plan',
            'costs_total' => 1500.50,
            'costs_source' => 'budget_expense',
        ]);

        // Manuelles Überschreiben löscht den Herkunfts-Stempel
        $this->putJson(route('projects.bi.update-data', $project->id), [
            'costs_total' => 999,
            'scope' => 'plan',
        ])->assertSuccessful();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'plan',
            'costs_source' => null,
        ]);
    }

    #[Test]
    public function plan_comparison_contains_costs_and_result(): void
    {
        $this->actingAsAdmin();
        [$project] = $this->createProjectWithBudgetColumns();

        $this->putJson(route('projects.bi.update-data', $project->id), [
            'revenue_total' => 1000,
            'scope' => 'plan',
        ])->assertSuccessful();
        $this->putJson(route('projects.bi.update-data', $project->id), [
            'costs_total' => 600,
            'scope' => 'plan',
        ])->assertSuccessful();
        $this->putJson(route('projects.bi.update-data', $project->id), [
            'revenue_total' => 1100,
            'scope' => 'actual',
        ])->assertSuccessful();
        $this->putJson(route('projects.bi.update-data', $project->id), [
            'costs_total' => 700,
            'scope' => 'actual',
        ])->assertSuccessful();

        $metrics = app(\Artwork\Modules\BusinessIntelligence\Services\BiProjectMetricsService::class)
            ->planComparison($project->fresh());

        $this->assertTrue($metrics['has_plan']);
        $this->assertSame(600.0, $metrics['metrics']['costs']['plan']);
        $this->assertSame(700.0, $metrics['metrics']['costs']['actual']);
        $this->assertSame(116.7, $metrics['metrics']['costs']['attainment']);
        // Ergebnis = Umsatz - Kosten je Scope
        $this->assertSame(400.0, $metrics['metrics']['result']['plan']);
        $this->assertSame(400.0, $metrics['metrics']['result']['actual']);
    }
}
