<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projekttitel mit "/" oder "\" (z. B. "Boss/y") landen im Download-Dateinamen; Content-Disposition
 * verbietet diese Zeichen, der Export brach deshalb mit einem Redirect ab. Ebenso brach der
 * Kalender-Export eines Projekts ohne Termine am fehlenden Zeitraum ab.
 */
final class EventExportDownloadTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function exportRoutes(): array
    {
        return [
            'Terminliste' => ['export.download-event-list-xlsx', 'event_list'],
            'Kalender' => ['export.download-calendar-xlsx', 'calendar'],
        ];
    }

    #[Test]
    #[DataProvider('exportRoutes')]
    public function export_succeeds_for_project_titles_containing_slashes(string $downloadRoute, string $type): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Boss/y\\Test']);
        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => '2026-11-10 10:00:00',
            'end_time' => '2026-11-10 12:00:00',
        ]);

        $cacheToken = $this->cacheExportConfiguration($user, $project, $type);

        $response = $this->actingAs($user)->get(route($downloadRoute, ['cacheToken' => $cacheToken]));

        $response->assertOk();
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('Boss-y-Test', rawurldecode($disposition));
    }

    #[Test]
    public function calendar_export_succeeds_for_project_without_events(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Ohne Termine']);

        $cacheToken = $this->cacheExportConfiguration($user, $project, 'calendar');

        $this->actingAs($user)
            ->get(route('export.download-calendar-xlsx', ['cacheToken' => $cacheToken]))
            ->assertOk()
            ->assertDownload();
    }

    private function cacheExportConfiguration(User $user, Project $project, string $type): string
    {
        return $this->actingAs($user)
            ->postJson(route('export.cache-filter'), [
                'desiresTimespanExport' => false,
                'desiresEventListExport' => $type === 'event_list',
                'desiredColumns' => ['event_id', 'project_name'],
                'conditional' => ['projects' => [$project->id]],
                'filter' => array_fill_keys(
                    [
                        'rooms',
                        'areas',
                        'roomCategories',
                        'roomAttributes',
                        'eventTypes',
                        'eventProperties',
                        'projectStates',
                    ],
                    []
                ),
            ])
            ->assertOk()
            ->getContent();
    }
}
