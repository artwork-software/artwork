<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Freier Zeitraumvergleich (Phase 4 des BI-Ausbaus): Dashboard mit wählbarem
 * Vergleichszeitraum und Kennzahlen-Summary je Zeitraum im Projekt-Tab.
 */
final class BiPeriodComparisonTest extends FeatureTestCase
{
    #[Test]
    public function dashboard_defaults_to_previous_year_comparison(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_DASHBOARD->value);

        $response = $this->get(route('bi.dashboard', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]))->assertOk();

        $dashboard = $response->viewData('page')['props']['dashboard'];

        $this->assertSame('2025-01-01', $dashboard['comparison_range']['from']);
        $this->assertSame('2025-12-31', $dashboard['comparison_range']['to']);
        $this->assertNotNull($dashboard['previous_kpis']);
    }

    #[Test]
    public function dashboard_accepts_a_free_comparison_range(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_DASHBOARD->value);

        $response = $this->get(route('bi.dashboard', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'compare_from' => '2023-08-01',
            'compare_to' => '2024-07-31',
        ]))->assertOk();

        $dashboard = $response->viewData('page')['props']['dashboard'];

        $this->assertSame('2023-08-01', $dashboard['comparison_range']['from']);
        $this->assertSame('2024-07-31', $dashboard['comparison_range']['to']);
    }

    #[Test]
    public function dashboard_comparison_can_be_disabled(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_DASHBOARD->value);

        $response = $this->get(route('bi.dashboard', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'compare' => 'none',
        ]))->assertOk();

        $dashboard = $response->viewData('page')['props']['dashboard'];

        $this->assertNull($dashboard['comparison_range']);
        $this->assertNull($dashboard['previous_kpis']);
    }

    #[Test]
    public function project_metrics_summary_returns_range_scoped_values(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([
            PermissionEnum::PROJECT_VIEW->value,
            PermissionEnum::WRITE_PROJECTS->value,
        ]);

        $this->putJson(route('projects.bi.update-data', $project), [
            'visitors_total' => 400,
        ])->assertOk();

        $response = $this->getJson(route('projects.bi.metrics-summary', [
            'project' => $project->id,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]))->assertOk();

        // TOTAL-Modus ist zeitraum-neutral → Wert erscheint unabhängig vom Range
        $this->assertSame(400, $response->json('summary.visitors'));
        $this->assertSame('2026-01-01', $response->json('range.from'));
    }

    #[Test]
    public function project_metrics_summary_requires_view_permission(): void
    {
        $project = Project::factory()->create();
        $this->actingAs(\Artwork\Modules\User\Models\User::factory()->create());

        $this->getJson(route('projects.bi.metrics-summary', $project))->assertForbidden();
    }
}
