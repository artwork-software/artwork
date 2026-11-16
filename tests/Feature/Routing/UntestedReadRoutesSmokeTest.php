<?php

namespace Tests\Feature\Routing;

use Artwork\Modules\BusinessIntelligence\Models\BiSnapshot;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lese-Routen, die bis zum 16.11.2026 von keinem Test aufgerufen wurden: als Admin dürfen sie
 * keinen Serverfehler liefern. Rechte prüft die AuthorizationMatrixTest.
 */
class UntestedReadRoutesSmokeTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1?: array<string, mixed>}>
     */
    public static function routesWithoutParameters(): array
    {
        return [
            'Hersteller' => ['manufacturers.index'],
            'Rechte-Referenz' => ['permissions.reference'],
            'Termineigenschaften' => ['event_settings.event_properties.index'],
            'Terminarten' => ['event_types.management'],
            'Terminarten BI-Tags' => ['event_types.bi_tags'],
            'Einstellungen Künstler*innenaufenthalte' => ['project.settings.artist_residencies'],
            'Arbeitszeitübersicht-Export' => [
                'shifts.export.work-time-overview',
                ['start' => '2026-11-01', 'end' => '2026-11-30'],
            ],
            'Sage-Protokolle' => ['tool.interfaces.sage.logs'],
            'Workflow-Übersicht' => ['workflow.index'],
            'Workflow-Definition anlegen' => ['workflow.definitions.create'],
            'Checklistenvorlagen-Suche' => ['checklist_templates.search', ['search' => 'a']],
            'Externe Ausgabe zum Kopieren suchen' => ['extern-issue-of-material.search-for-copy', ['search' => 'a']],
            'Chats' => ['chat-system.get-chats'],
            'BI-Publikumskategorien' => ['bi.audience-categories.index'],
            'Dienstplan-Termine' => [
                'shift.plan.all',
                ['start' => '2026-11-16', 'end' => '2026-11-22'],
            ],
            'Personen für individuelle Zeiten' => ['api.individual-times.subjects', ['search' => 'a']],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[Test]
    #[DataProvider('routesWithoutParameters')]
    public function read_route_does_not_fail_for_admins(string $routeName, array $query = []): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route($routeName, $query));

        self::assertLessThan(500, $response->getStatusCode(), $routeName . ': ' . $response->getContent());
    }

    #[Test]
    public function internal_issue_show_and_print_do_not_fail(): void
    {
        $this->actingAsAdmin();
        $issue = InternalIssue::factory()->create();

        foreach (['issue-of-material.show', 'issue-of-material.print'] as $routeName) {
            $response = $this->get(route($routeName, $issue));
            self::assertLessThan(500, $response->getStatusCode(), $routeName . ': ' . $response->getContent());
        }
    }

    #[Test]
    public function shift_plan_change_list_per_craft_does_not_fail(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $response = $this->get(route('shifts.approvals.changes-craft', ['craft' => $craft->id]));

        self::assertLessThan(500, $response->getStatusCode(), $response->getContent());
    }

    #[Test]
    public function project_bi_pages_do_not_fail(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $snapshot = BiSnapshot::factory()->create(['project_id' => $project->id]);

        $capacities = $this->getJson(route('projects.bi.room-capacities', $project));
        self::assertLessThan(500, $capacities->getStatusCode(), $capacities->getContent());

        $show = $this->getJson(route('projects.bi.snapshots.show', [$project, $snapshot]));
        self::assertLessThan(500, $show->getStatusCode(), $show->getContent());
    }

    #[Test]
    public function task_edit_does_not_fail(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $response = $this->get('/tasks/' . $task->id . '/edit');

        self::assertLessThan(500, $response->getStatusCode(), $response->getContent());
    }
}
