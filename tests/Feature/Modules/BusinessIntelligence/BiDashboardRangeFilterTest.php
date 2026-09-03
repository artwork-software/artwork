<?php

namespace Tests\Feature\Modules\BusinessIntelligence;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\BusinessIntelligence\Services\BiDashboardService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BiDashboardRangeFilterTest extends FeatureTestCase
{
    private function createProjectWithEvent(string $name, string $start, string $end, ?int $visitorsTotal = null): Project
    {
        $project = Project::factory()->create(['name' => $name, 'is_group' => false]);
        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        if ($visitorsTotal !== null) {
            BiProjectData::create([
                'project_id' => $project->id,
                'scope' => 'actual',
                'visitor_mode' => 'total',
                'visitors_total' => $visitorsTotal,
            ]);
        }

        return $project;
    }

    private function projectNames(array $dashboard): array
    {
        return array_column($dashboard['projects'], 'project_name');
    }

    #[Test]
    public function effort_table_only_lists_projects_taking_place_in_the_range(): void
    {
        $this->createProjectWithEvent('In Range', '2026-05-10 19:00:00', '2026-05-10 22:00:00', 100);
        $this->createProjectWithEvent('Out Of Range', '2026-09-01 19:00:00', '2026-09-01 22:00:00', 50);

        // Projekt ganz ohne Termine: findet im Zeitraum nicht statt
        $eventless = Project::factory()->create(['name' => 'No Events', 'is_group' => false]);
        BiProjectData::create([
            'project_id' => $eventless->id,
            'scope' => 'actual',
            'visitor_mode' => 'total',
            'visitors_total' => 30,
        ]);

        $dashboard = app(BiDashboardService::class)->getDashboardData(
            '2026-05-01',
            '2026-05-31',
            noCompare: true
        );

        $this->assertSame(['In Range'], $this->projectNames($dashboard));
        $this->assertSame(1, $dashboard['kpis']['project_count']);
        // TOTAL-Modus-Werte der herausgefilterten Projekte dürfen die
        // Haus-KPIs nicht mehr verwässern (vorher: 100 + 50 + 30)
        $this->assertSame(100, $dashboard['kpis']['visitors']);
    }

    #[Test]
    public function events_overlapping_the_range_boundary_keep_their_project_visible(): void
    {
        // Termin beginnt vor dem Zeitraum, endet darin → Projekt findet statt
        $this->createProjectWithEvent('Overlapping', '2026-04-30 22:00:00', '2026-05-01 02:00:00');

        $dashboard = app(BiDashboardService::class)->getDashboardData(
            '2026-05-01',
            '2026-05-31',
            noCompare: true
        );

        $this->assertSame(['Overlapping'], $this->projectNames($dashboard));
    }

    #[Test]
    public function without_a_range_all_projects_stay_visible(): void
    {
        $this->createProjectWithEvent('With Event', '2026-05-10 19:00:00', '2026-05-10 22:00:00');
        Project::factory()->create(['name' => 'No Events', 'is_group' => false]);

        $dashboard = app(BiDashboardService::class)->getDashboardData(noCompare: true);

        $names = $this->projectNames($dashboard);
        $this->assertContains('With Event', $names);
        $this->assertContains('No Events', $names);
    }

    #[Test]
    public function comparison_run_filters_by_the_comparison_range(): void
    {
        $current = $this->createProjectWithEvent('Current Season', '2026-05-10 19:00:00', '2026-05-10 22:00:00');
        // Nur im Vorjahreszeitraum aktiv → zählt in previous_kpis, nicht in kpis
        $last = $this->createProjectWithEvent('Last Season', '2025-05-12 19:00:00', '2025-05-12 22:00:00');

        // Veranstaltungstage zählen nur mit zugeordnetem KPI-Tag (kein Fallback)
        $tag = BiEventTypeTag::create(['name' => 'Event day', 'name_de' => 'Veranstaltungstag', 'color' => '#6366f1']);
        $tag->eventTypes()->sync(
            Event::query()->whereIn('project_id', [$current->id, $last->id])->pluck('event_type_id')->unique()->all()
        );

        $dashboard = app(BiDashboardService::class)->getDashboardData('2026-05-01', '2026-05-31');

        $this->assertSame(['Current Season'], $this->projectNames($dashboard));
        $this->assertSame(1, $dashboard['comparable_kpis']['event_days']);
        $this->assertSame(1, $dashboard['previous_kpis']['event_days']);
        $this->assertTrue($dashboard['kpi_tags']['event_day']);
        // Vorstellungs-Tag fehlt → keine Vorstellungen, keine Auslastung, Hinweis-Flag aus
        $this->assertFalse($dashboard['kpi_tags']['performance']);
        $this->assertNull($dashboard['kpis']['performances']);
        $this->assertFalse($dashboard['tags_linked']);
    }

    #[Test]
    public function category_filter_narrows_every_aggregate_but_keeps_the_category_list(): void
    {
        $this->createProjectWithEvent('Uncategorised', '2026-05-10 19:00:00', '2026-05-10 22:00:00', 40);

        $service = app(BiDashboardService::class);

        // Sparten-Liste kommt aus dem ungefilterten Bestand ('—' = ohne Sparte)
        $unfiltered = $service->getDashboardData('2026-05-01', '2026-05-31', noCompare: true);
        $this->assertNull($unfiltered['category_filter']);
        $this->assertSame([['category' => '—', 'project_count' => 1]], $unfiltered['categories']);

        $filtered = $service->getDashboardData('2026-05-01', '2026-05-31', noCompare: true, category: 'Oper');
        $this->assertSame('Oper', $filtered['category_filter']);
        $this->assertSame([], $filtered['projects']);
        $this->assertSame(0, $filtered['kpis']['project_count']);
        $this->assertSame([], $filtered['data_gaps']);
        // Liste bleibt, damit man die Sparte wieder wechseln kann
        $this->assertSame([['category' => '—', 'project_count' => 1]], $filtered['categories']);

        $none = $service->getDashboardData('2026-05-01', '2026-05-31', noCompare: true, category: '—');
        $this->assertSame(['Uncategorised'], $this->projectNames($none));
    }

    #[Test]
    public function unlinked_kpi_tags_leave_performances_and_event_days_empty(): void
    {
        $this->createProjectWithEvent('Rehearsal Only', '2026-05-10 19:00:00', '2026-05-10 22:00:00');

        $dashboard = app(BiDashboardService::class)->getDashboardData('2026-05-01', '2026-05-31', noCompare: true);

        $this->assertNull($dashboard['kpis']['performances']);
        $this->assertNull($dashboard['kpis']['event_days']);
        $this->assertNull($dashboard['projects'][0]['performances']);
        $this->assertNull($dashboard['projects'][0]['event_days']);
    }
}
