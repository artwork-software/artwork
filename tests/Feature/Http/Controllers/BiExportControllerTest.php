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

        // Produktionen + Termine + Info
        self::assertCount(3, $sheets);

        [$projectSheet, $eventSheet] = $sheets;

        // Projektblatt: Kopf + eine Zeile pro Projekt; project_id immer vorn
        self::assertSame(__('Project ID'), $projectSheet[0][0]);
        self::assertEquals($project->id, $projectSheet[1][0]);
        self::assertSame($project->name, $projectSheet[1][1]);
        self::assertCount(2, $projectSheet);

        // Terminblatt: Kopf + eine Zeile pro Termin, IDs + Projektname in jeder Zeile
        self::assertCount(3, $eventSheet);
        self::assertEquals($project->id, $eventSheet[1][1]);
        self::assertSame($project->name, $eventSheet[1][2]);
        self::assertSame($project->name, $eventSheet[2][2]);
    }

    #[Test]
    public function workbook_ends_with_an_info_sheet_describing_the_export(): void
    {
        [$project] = $this->createProjectWithTaggedAndUntaggedEvent();

        $sheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => ['project_name'],
            'granularity' => 'projects',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'user_name' => 'Kim Test',
        ]);

        $info = end($sheets);
        $labels = array_column($info, 0);
        $values = array_column($info, 1);

        self::assertContains(__('Created by'), $labels);
        self::assertSame('Kim Test', $values[array_search(__('Created by'), $labels, true)]);
        self::assertSame('01.01.2026 – 31.12.2026', $values[array_search(__('Period'), $labels, true)]);
        self::assertSame(__('Chosen in the export dialog'), $values[array_search(__('Period source'), $labels, true)]);
    }

    #[Test]
    public function columns_follow_the_catalog_order_not_the_click_order(): void
    {
        $project = Project::factory()->create();

        $sheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => ['event_count', 'project_name', 'visitors'],
            'granularity' => 'projects',
        ]);

        self::assertSame(
            [__('Project ID'), __('Project name'), __('Visitors'), __('Events')],
            $sheets[0][0]
        );
    }

    #[Test]
    public function unknown_columns_are_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $this->postJson(route('bi.export.cache'), [
            'project_ids' => [Project::factory()->create()->id],
            'columns' => ['project_name', 'does_not_exist'],
            'granularity' => 'projects',
        ])->assertUnprocessable()->assertJsonValidationErrors('columns.1');
    }

    #[Test]
    public function booleans_dates_and_ratios_are_exported_as_native_excel_values(): void
    {
        $project = Project::factory()->create();
        \Artwork\Modules\BusinessIntelligence\Models\BiProjectData::create([
            'project_id' => $project->id,
            'scope' => 'actual',
            'is_own_production' => true,
            'is_co_production' => false,
            'premiere_date' => '2026-03-15',
        ]);

        $sheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => ['is_own_production', 'is_co_production', 'premiere_date'],
            'granularity' => 'projects',
        ]);

        $header = $sheets[0][0];
        $row = $sheets[0][1];
        $at = fn (string $label) => $row[array_search(__($label), $header, true)];
        self::assertTrue($at('Own production'));
        self::assertFalse($at('Co-production'));
        // Excel-Serienwert (Tage seit 1900) statt Text
        self::assertIsNumeric($at('Premiere date'));
        self::assertSame(
            '2026-03-15',
            \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($at('Premiere date'))->format('Y-m-d')
        );
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

        self::assertCount(2, $taggedSheets);
        self::assertCount(2, $taggedSheets[0]);
        self::assertSame($taggedEvent->event_type->name, $taggedSheets[0][1][$this->eventColumnIndex('event_type')]);

        $untaggedSheets = $this->generateWorkbookSheets([
            'project_ids' => [$project->id],
            'columns' => [],
            'granularity' => 'events',
            'event_tag_filter' => ['untagged'],
        ]);

        self::assertCount(2, $untaggedSheets[0]);
        self::assertSame($untaggedEvent->event_type->name, $untaggedSheets[0][1][$this->eventColumnIndex('event_type')]);
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
        $typeIndex = $this->eventColumnIndex('event_type');
        $taggedRow = collect($rows)->first(
            fn (array $row) => $row[$typeIndex] === $taggedEvent->event_type->name
        );

        self::assertNotNull($taggedRow);
        self::assertEquals(120, $taggedRow[$this->eventColumnIndex('visitors')]);
        self::assertEquals(100, $taggedRow[$this->eventColumnIndex('sold_tickets')]);
        self::assertEquals(2500.50, $taggedRow[$this->eventColumnIndex('revenue')]);
    }

    #[Test]
    public function dashboard_exposes_export_permission_and_options_endpoint_requires_it(): void
    {
        CostCenter::create(['name' => 'KT 100']);

        $this->actingAsUserWith(PermissionEnum::BI_DASHBOARD->value);

        $this->get(route('bi.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canExportBiData', false));
        $this->getJson(route('bi.export.options'))->assertForbidden();

        $this->actingAsUserWith([
            PermissionEnum::BI_DASHBOARD->value,
            PermissionEnum::BI_EXPORT->value,
        ]);

        $this->get(route('bi.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canExportBiData', true));

        $this->getJson(route('bi.export.options'))
            ->assertOk()
            ->assertJsonStructure([
                'projects', 'costCenters', 'columns', 'tagColumns', 'presets', 'defaultColumns',
                'columnGroups' => [['key', 'label', 'default', 'columns']],
            ])
            ->assertJsonCount(1, 'costCenters');
    }

    #[Test]
    public function presets_can_only_be_changed_by_their_creator_or_an_admin(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $created = $this->postJson(route('bi.export.presets.store'), [
            'name' => 'Leitungssicht',
            'columns' => ['visitors', 'project_name'],
        ])->assertOk()->json();

        // Katalogreihenfolge gespeichert, project_id vorn
        self::assertSame(['project_id', 'project_name', 'visitors'], $created['columns']);
        self::assertTrue($created['can_manage']);

        // Gleicher Name ist nicht erlaubt
        $this->postJson(route('bi.export.presets.store'), [
            'name' => 'Leitungssicht',
            'columns' => ['visitors'],
        ])->assertUnprocessable()->assertJsonValidationErrors('name');

        // Andere Person mit Exportrecht: sehen ja, ändern/löschen nein
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);
        $this->getJson(route('bi.export.presets.index'))
            ->assertOk()
            ->assertJsonPath('0.can_manage', false);
        $this->putJson(route('bi.export.presets.update', $created['id']), ['name' => 'Neu'])->assertForbidden();
        $this->deleteJson(route('bi.export.presets.destroy', $created['id']))->assertForbidden();
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

        // Info-Blatt hängt immer hinten dran
        self::assertCount(2, $sheets);
        self::assertSame($project->name, $sheets[0][1][1]);
        self::assertSame('KT 4711', $sheets[0][1][2]);
    }

    private function eventColumnIndex(string $key): int
    {
        return array_search($key, array_keys(BiExportService::eventColumnLabelMap()), true);
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
