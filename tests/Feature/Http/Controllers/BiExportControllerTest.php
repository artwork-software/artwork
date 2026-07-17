<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Jobs\GenerateBiExportJob;
use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BiExportControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_cache_export_configuration(): void
    {
        $this->postJson(route('bi.export.cache'), [])->assertUnauthorized();
    }

    #[Test]
    public function user_without_permission_cannot_cache_export_configuration(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('bi.export.cache'), [
            'project_ids' => [Project::factory()->create()->id],
            'columns' => ['project_name'],
        ])->assertForbidden();
    }

    #[Test]
    public function columns_are_required_for_project_granularity(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $this->postJson(route('bi.export.cache'), [
            'project_ids' => [Project::factory()->create()->id],
            'granularity' => 'both',
        ])->assertUnprocessable()->assertJsonValidationErrors('columns');
    }

    #[Test]
    public function event_only_export_does_not_require_columns(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $this->postJson(route('bi.export.cache'), [
            'project_ids' => [Project::factory()->create()->id],
            'granularity' => 'events',
        ])->assertOk()->assertJsonStructure(['token']);

        Bus::assertDispatched(GenerateBiExportJob::class);
    }

    #[Test]
    public function invalid_event_tag_filter_values_are_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $this->postJson(route('bi.export.cache'), [
            'project_ids' => [Project::factory()->create()->id],
            'granularity' => 'events',
            'event_tag_filter' => ['nonsense'],
        ])->assertUnprocessable()->assertJsonValidationErrors('event_tag_filter.0');

        $this->postJson(route('bi.export.cache'), [
            'project_ids' => [Project::factory()->create()->id],
            'granularity' => 'events',
            'event_tag_filter' => [999999],
        ])->assertUnprocessable()->assertJsonValidationErrors('event_tag_filter.0');
    }

    #[Test]
    public function export_with_both_granularities_contains_project_and_event_sheets(): void
    {
        [$project] = $this->createProjectWithTaggedAndUntaggedEvent();

        $sheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => ['project_name', 'event_count'],
            'granularity' => 'both',
            'event_tag_filter' => [],
        ]);

        self::assertCount(2, $sheets);

        [$projectSheet, $eventSheet] = $sheets;

        // Projektblatt: Kopf + eine Zeile pro Projekt
        self::assertSame($project->name, $projectSheet[1][0]);
        self::assertCount(2, $projectSheet);

        // Terminblatt: Kopf + eine Zeile pro Termin, Projektname in jeder Zeile
        self::assertCount(3, $eventSheet);
        self::assertSame($project->name, $eventSheet[1][0]);
        self::assertSame($project->name, $eventSheet[2][0]);
    }

    #[Test]
    public function event_sheet_can_be_filtered_by_bi_tag_and_untagged(): void
    {
        [$project, $tag, $taggedEvent, $untaggedEvent] = $this->createProjectWithTaggedAndUntaggedEvent();

        $taggedSheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => [],
            'granularity' => 'events',
            'event_tag_filter' => [$tag->id],
        ]);

        self::assertCount(1, $taggedSheets);
        self::assertCount(2, $taggedSheets[0]);
        self::assertSame($taggedEvent->event_type->name, $taggedSheets[0][1][3]);

        $untaggedSheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => [],
            'granularity' => 'events',
            'event_tag_filter' => ['untagged'],
        ]);

        self::assertCount(2, $untaggedSheets[0]);
        self::assertSame($untaggedEvent->event_type->name, $untaggedSheets[0][1][3]);
    }

    #[Test]
    public function event_sheet_contains_per_event_bi_data(): void
    {
        [$project, , $taggedEvent] = $this->createProjectWithTaggedAndUntaggedEvent();

        BiEventData::create([
            'project_id' => $project->id,
            'event_id' => $taggedEvent->id,
            'visitors' => 120,
            'sold_tickets' => 100,
            'revenue' => 2500.50,
        ]);

        $sheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => [],
            'granularity' => 'events',
            'event_tag_filter' => [],
        ]);

        $rows = $sheets[0];
        $taggedRow = collect($rows)->first(
            fn (array $row) => $row[3] === $taggedEvent->event_type->name
        );

        self::assertNotNull($taggedRow);
        self::assertEquals(120, $taggedRow[7]);
        self::assertEquals(100, $taggedRow[8]);
        self::assertEquals(2500.50, $taggedRow[9]);
    }

    #[Test]
    public function dashboard_contains_export_options_only_with_export_permission(): void
    {
        CostCenter::create(['name' => 'KT 100']);

        $this->actingAsUserWith(PermissionEnum::BI_DASHBOARD->value);

        $this->get(route('bi.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('exportOptions', null));

        $this->actingAsUserWith([
            PermissionEnum::BI_DASHBOARD->value,
            PermissionEnum::BI_EXPORT->value,
        ]);

        $this->get(route('bi.dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->has('exportOptions.projects')
                    ->has('exportOptions.costCenters', 1)
                    ->has('exportOptions.columns')
                    ->has('exportOptions.tagColumns')
                    ->has('exportOptions.presets')
            );
    }

    #[Test]
    public function project_sheet_contains_cost_center_column(): void
    {
        $costCenter = CostCenter::create(['name' => 'KT 4711']);
        $project = Project::factory()->create(['cost_center_id' => $costCenter->id]);

        $sheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => ['project_name', 'cost_center'],
            'granularity' => 'projects',
        ]);

        self::assertCount(1, $sheets);
        self::assertSame($project->name, $sheets[0][1][0]);
        self::assertSame('KT 4711', $sheets[0][1][1]);
    }

    /**
     * @return array{0: Project, 1: BiEventTypeTag, 2: Event, 3: Event}
     */
    private function createProjectWithTaggedAndUntaggedEvent(): array
    {
        $project = Project::factory()->create();

        $taggedType = EventType::factory()->create(['name' => 'Vorstellung']);
        $untaggedType = EventType::factory()->create(['name' => 'Probe']);

        $tag = BiEventTypeTag::create(['name' => 'Event days', 'name_de' => 'Veranstaltungstage']);
        $tag->eventTypes()->attach($taggedType->id);

        $taggedEvent = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $taggedType->id,
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(22, 0),
            'allDay' => false,
        ]);

        $untaggedEvent = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $untaggedType->id,
            'start_time' => now()->addDay()->setTime(10, 0),
            'end_time' => now()->addDay()->setTime(12, 0),
            'allDay' => false,
        ]);

        return [$project, $tag, $taggedEvent, $untaggedEvent];
    }

    /**
     * Generates the export through the real service and returns every sheet
     * as a plain array matrix (rows of cell values).
     *
     * @return array<int, array<int, array<int, mixed>>>
     */
    private function generateWorkbookSheets(array $config): array
    {
        $service = app(BiExportService::class);

        $token = $service->cacheExportConfiguration($config);
        $service->generateAndStore($token);

        self::assertSame('ready', $service->getStatus($token)['status']);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path('bi-exports/' . $token . '.xlsx'));

        $sheets = [];
        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            // formatData=false: Rohwerte statt Anzeigeformat (z.B. "2,500.50 €")
            $sheets[] = $worksheet->toArray(null, true, false);
        }

        return $sheets;
    }
}
