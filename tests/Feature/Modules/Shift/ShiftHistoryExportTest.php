<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Exports\ShiftHistoryExcelExport;
use Artwork\Modules\Shift\Exports\Support\ShiftActivityPresenter;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftHistoryQueryService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

/**
 * Excel-Export des Schichtverlaufs (shift-history.export): Rechte, gleiche Filter wie das Modal,
 * übersetzte Details, Kopfzeile, Query-Budget.
 */
final class ShiftHistoryExportTest extends FeatureTestCase
{
    private function makeShift(array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ], $attributes));
    }

    private function logActivity(Shift $shift, string $description, array $properties = [], ?User $causer = null): Activity
    {
        $logger = activity('shift')->performedOn($shift)->withProperties($properties);
        if ($causer) {
            $logger->causedBy($causer);
        }

        return $logger->log($description);
    }

    #[Test]
    public function export_requires_view_shift_plan_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->get(route('shift-history.export', ['start_date' => '2026-05-01', 'end_date' => '2026-05-31']))
            ->assertForbidden();
    }

    #[Test]
    public function export_downloads_with_period_in_filename_and_applies_search_and_craft_filter(): void
    {
        Excel::fake();
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $shift = $this->makeShift();
        $otherCraftShift = $this->makeShift();

        $this->logActivity($shift, 'User assigned to shift', [
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => ['Zaphod Beeblebrox', 'Tech', 'Stage', 'ST'],
        ]);
        $this->logActivity($shift, 'shift updated noise');
        $this->logActivity($otherCraftShift, 'User assigned to shift', [
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => ['Zaphod Beeblebrox', 'Tech', 'Stage', 'ST'],
        ]);

        $this->get(route('shift-history.export', [
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'search' => 'zaphod',
        ]))->assertOk();

        Excel::assertDownloaded(
            'schichtverlauf_2026-05-01_bis_2026-05-31.xlsx',
            function (ShiftHistoryExcelExport $export) use ($shift): bool {
                $rows = $export->query()->get();

                return $rows->count() === 1 && (int) $rows->first()->subject_id === $shift->id;
            }
        );
    }

    #[Test]
    public function export_rows_carry_translated_details_and_shift_data(): void
    {
        $causer = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value, User::factory()->create([
            'first_name' => 'Petra',
            'last_name' => 'Planer',
        ]));
        $shift = $this->makeShift();
        $this->logActivity($shift, 'User assigned to shift', [
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => ['Zaphod Beeblebrox', 'Tech', 'Stage', 'ST'],
        ], $causer);

        $service = app(ShiftHistoryQueryService::class);
        $filters = $service->resolveFilters([
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]);
        $matched = $service->matchedShiftIds($filters, [$shift->id]);
        $export = new ShiftHistoryExcelExport($service->activityQuery($filters, $matched), $matched, 'de');

        $headings = $export->headings();
        $this->assertCount(8, $headings);
        $this->assertSame(__('Timestamp', [], 'de'), $headings[0]);
        $this->assertSame(__('Details', [], 'de'), $headings[7]);

        $row = $export->map($export->query()->first());
        $this->assertSame('Petra Planer', $row[1]);
        $this->assertSame(__('Staffing', [], 'de'), $row[2]);
        $this->assertSame('06.05.2026', $row[3]);
        $this->assertSame('09:00 – 17:00', $row[4]);
        // Details: Übersetzungsschlüssel mit eingesetzten Platzhaltern — wie im Modal
        $expected = (new ShiftActivityPresenter('de'))->message($export->query()->first());
        $this->assertSame($expected, $row[7]);
        $this->assertStringContainsString('Zaphod Beeblebrox', $row[7]);
        $this->assertStringNotContainsString('{0}', $row[7]);
    }

    #[Test]
    public function export_reads_large_histories_in_chunks_within_a_query_budget(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $shift = $this->makeShift();

        // Die Schicht-Anlage selbst hinterlässt bereits Verlaufseinträge → Baseline vor dem Einfügen zählen
        $baselineService = app(ShiftHistoryQueryService::class);
        $baselineFilters = $baselineService->resolveFilters([
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]);
        $preExisting = $baselineService
            ->activityQuery($baselineFilters, $baselineService->matchedShiftIds($baselineFilters, [$shift->id]))
            ->count();

        $rows = [];
        for ($i = 0; $i < 1100; $i++) {
            $rows[] = [
                'log_name' => 'shift',
                'description' => 'shift updated ' . $i,
                'subject_type' => Shift::class,
                'subject_id' => $shift->id,
                'event' => 'updated',
                'properties' => json_encode(['attributes' => ['start' => '10:00'], 'old' => ['start' => '09:00']]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table('activity_log')->insert($chunk);
        }

        $service = app(ShiftHistoryQueryService::class);
        $filters = $service->resolveFilters([
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]);
        $matched = $service->matchedShiftIds($filters, [$shift->id]);
        $export = new ShiftHistoryExcelExport($service->activityQuery($filters, $matched), $matched, 'de');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $mapped = 0;
        $export->query()->chunk($export->chunkSize(), function ($logs) use ($export, &$mapped): void {
            foreach ($logs as $log) {
                $export->map($log);
                $mapped++;
            }
        });
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1100 + $preExisting, $mapped);
        // 3 Chunks à (1 Hauptquery + causer-Eager-Load) + einmalige Schicht-Lookups — kein N+1 je Zeile
        $this->assertLessThan(20, $queries, "Export brauchte {$queries} Queries für 1100 Zeilen");
    }


    #[Test]
    public function export_rejects_periods_longer_than_one_year(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->getJson(route('shift-history.export', ['start_date' => '2025-01-01', 'end_date' => '2026-01-03']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // Genau ein Jahr (366 Tage) bleibt erlaubt
        Excel::fake();
        $this->get(route('shift-history.export', ['start_date' => '2025-01-01', 'end_date' => '2026-01-02']))
            ->assertOk();
    }

    #[Test]
    public function export_with_a_single_bound_is_limited_to_one_year(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        Excel::fake();

        // Nur "von": bis = von + 366 Tage (kein unbegrenzter Export, kein 422)
        $this->get(route('shift-history.export', ['start_date' => '2020-01-01']))->assertOk();
        Excel::assertDownloaded('schichtverlauf_2020-01-01_bis_2021-01-01.xlsx');

        // Nur "bis": von = bis − 366 Tage
        $this->get(route('shift-history.export', ['end_date' => '2021-01-01']))->assertOk();
        Excel::assertDownloaded('schichtverlauf_2020-01-01_bis_2021-01-01.xlsx');
    }
}
