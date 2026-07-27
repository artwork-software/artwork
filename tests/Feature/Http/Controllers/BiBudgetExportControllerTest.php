<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Jobs\GenerateBiBudgetExportJob;
use Artwork\Modules\BusinessIntelligence\Services\BiBudgetExportService;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projektunabhängiger Budget-Export (BI-Ausbau Baustein G): KTO/KST-Präfixfilter
 * auf die ersten beiden Budget-Spalten, Projektzeitraum-Scope, zwei Blätter.
 */
final class BiBudgetExportControllerTest extends FeatureTestCase
{
    /**
     * Projekt mit Termin im Juni 2026 und einer Budget-Zeile:
     * KTO=5400, KST=201, Position=Ticketing, Wertspalte 1000, eine Sage-Buchung.
     *
     * @return array{project: Project, row: SubPositionRow}
     */
    private function makeBudgetProject(string $kto = '5400', string $kst = '201'): array
    {
        $project = Project::factory()->create();
        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => '2026-06-10 19:00:00',
            'end_time' => '2026-06-10 22:00:00',
        ]);

        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);

        $ktoColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'name' => 'KTO', 'type' => 'empty']);
        $kstColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 1, 'name' => 'KST', 'type' => 'empty']);
        $positionColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 2, 'name' => 'Position', 'type' => 'empty']);
        $valueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 3, 'name' => 'Kalkulation 2026', 'type' => 'empty']);
        $sageColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'name' => 'Sage', 'type' => 'sage']);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id, 'name' => 'Erlöse']);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id, 'name' => 'Ticketing']);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        ColumnCell::factory()->create(['column_id' => $ktoColumn->id, 'sub_position_row_id' => $row->id, 'value' => $kto]);
        ColumnCell::factory()->create(['column_id' => $kstColumn->id, 'sub_position_row_id' => $row->id, 'value' => $kst]);
        ColumnCell::factory()->create(['column_id' => $positionColumn->id, 'sub_position_row_id' => $row->id, 'value' => 'Ticketerlöse']);
        ColumnCell::factory()->create(['column_id' => $valueColumn->id, 'sub_position_row_id' => $row->id, 'value' => '1000']);
        $sageCell = ColumnCell::factory()->create(['column_id' => $sageColumn->id, 'sub_position_row_id' => $row->id, 'value' => '0,00']);

        SageAssignedData::create([
            'column_cell_id' => $sageCell->id,
            'sage_id' => 1,
            'tan' => 1,
            'periode' => 202606,
            'kto_haben' => '5400',
            'kreditor' => 'Kasse',
            'buchungstext' => 'Ticketverkauf Juni',
            'buchungsbetrag' => -250.5,
            'belegnummer' => 'B-1',
            'belegdatum' => '2026-06-11',
            'kto_soll' => '',
            'sa_kto' => '5400',
            'kst_traeger' => 'KT1',
            'kst_stelle' => '201',
            'buchungsdatum' => '2026-06-12',
            'is_collective_booking' => false,
        ]);

        return ['project' => $project, 'row' => $row];
    }

    #[Test]
    public function budget_export_routes_require_export_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('bi.budget-export.options'))->assertForbidden();
        $this->postJson(route('bi.budget-export.cache'), [])->assertForbidden();
    }

    #[Test]
    public function options_return_configured_column_names(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $response = $this->getJson(route('bi.budget-export.options'))->assertOk();

        // Ohne Settings greifen die Seed-Defaults
        $this->assertNotEmpty($response->json('ktoColumnName'));
        $this->assertNotEmpty($response->json('kstColumnName'));
    }

    #[Test]
    public function match_counts_count_rows_per_column_prefix(): void
    {
        $this->makeBudgetProject('5400', '201');
        $this->makeBudgetProject('6100', '540');
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $response = $this->getJson(route('bi.budget-export.match-counts', [
            'prefix' => '54',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]))->assertOk();

        // "54" matcht KTO 5400 (Projekt 1) und KST 540 (Projekt 2)
        $this->assertSame(1, $response->json('kto'));
        $this->assertSame(1, $response->json('kst'));
    }

    #[Test]
    public function cache_dispatches_the_budget_export_job(): void
    {
        $this->actingAsUserWith(PermissionEnum::BI_EXPORT->value);

        $this->postJson(route('bi.budget-export.cache'), [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ])->assertOk();

        Bus::assertDispatched(GenerateBiBudgetExportJob::class);
    }

    /**
     * Sage-Teile des Exports sind an die aktive Schnittstelle gebunden —
     * für diesen Test wird sie als aktiv gemockt (Test-Env: SAGE_API_ENABLED=false).
     */
    private function enableSage(): void
    {
        $this->partialMock(
            \Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService::class,
            fn($mock) => $mock->shouldReceive('isSageApiEnabled')->andReturn(true)
        );
    }

    #[Test]
    public function export_contains_budget_rows_and_sage_bookings(): void
    {
        $this->enableSage();
        ['project' => $project] = $this->makeBudgetProject('5400', '201');
        // Projekt außerhalb des Zeitraums darf nicht auftauchen — Termin 2024
        $outOfRange = $this->makeBudgetProject('9999', '999');
        $outOfRange['project']->events()->update([
            'start_time' => '2024-06-10 19:00:00',
            'end_time' => '2024-06-10 22:00:00',
        ]);

        $service = app(BiBudgetExportService::class);
        $token = $service->cacheExportConfiguration([
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'filters' => [['column' => 'kto', 'prefix' => '54']],
        ]);
        $service->generateAndStore($token);

        $path = Storage::disk('local')->path('bi-exports/budget-' . $token . '.xlsx');
        $spreadsheet = IOFactory::load($path);

        $rowsSheet = $spreadsheet->getSheetByName(__('Budget rows'))->toArray(null, true, false);
        // Kopfzeile + genau eine getroffene Zeile
        $this->assertCount(2, $rowsSheet);
        $this->assertSame($project->name, $rowsSheet[1][0]);
        // Numerische Zellwerte kommen als Zahl aus dem Reader → auf Strings normalisieren
        $stringRow = array_map('strval', $rowsSheet[1]);
        $this->assertContains('5400', $stringRow);
        $this->assertContains('Ticketerlöse', $stringRow);
        // Sage-Ist-Summe (-250.5) in der letzten Spalte
        $this->assertEqualsWithDelta(-250.5, (float) end($rowsSheet[1]), 0.01);

        $bookingsSheet = $spreadsheet->getSheetByName(__('Sage bookings'))->toArray(null, true, false);
        $this->assertCount(2, $bookingsSheet);
        $this->assertContains('Ticketverkauf Juni', $bookingsSheet[1]);
    }

    #[Test]
    public function export_without_active_sage_interface_has_no_sage_sheet(): void
    {
        $this->makeBudgetProject('5400', '201');

        $service = app(BiBudgetExportService::class);
        $token = $service->cacheExportConfiguration([
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]);
        $service->generateAndStore($token);

        $path = Storage::disk('local')->path('bi-exports/budget-' . $token . '.xlsx');
        $spreadsheet = IOFactory::load($path);

        $this->assertNull($spreadsheet->getSheetByName(__('Sage bookings')));
        $rowsSheet = $spreadsheet->getSheetByName(__('Budget rows'))->toArray(null, true, false);
        $this->assertNotContains(__('Sage actual'), $rowsSheet[0]);
        // Budget-Zeile selbst bleibt vorhanden
        $this->assertCount(2, $rowsSheet);
    }

    #[Test]
    public function booking_date_restriction_filters_sage_bookings(): void
    {
        $this->enableSage();
        $this->makeBudgetProject('5400', '201');

        $service = app(BiBudgetExportService::class);
        $token = $service->cacheExportConfiguration([
            'date_from' => '2026-01-01',
            'date_to' => '2026-03-31',
        ]);
        $service->generateAndStore($token);

        // Projekt hat Termine nur im Juni → gar nicht im Scope
        $path = Storage::disk('local')->path('bi-exports/budget-' . $token . '.xlsx');
        $spreadsheet = IOFactory::load($path);
        $rowsSheet = $spreadsheet->getSheetByName(__('Budget rows'))->toArray(null, true, false);
        $this->assertCount(1, $rowsSheet);
    }
}
