<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Exports\ShiftRuleViolationsExcelExport;
use Artwork\Modules\Shift\Exports\Support\ViolationMeasureFormatter;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\ShiftRuleViolationRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Excel-Export der Regelverstöße (shift-rules.violations.export): Rechte, Filter, Kopfzeile,
 * Messwert-Formatierung und Query-Budget beim Chunking.
 */
final class ShiftRuleViolationsExportTest extends FeatureTestCase
{
    private function planner(): User
    {
        return $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value,
        ]);
    }

    private function violation(User $user, ShiftRule $rule, array $overrides = []): ShiftRuleViolation
    {
        return ShiftRuleViolation::factory()->create(array_merge([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => Carbon::today()->toDateString(),
            'severity' => 'warning',
            'status' => 'active',
            'violation_data' => ['planned_hours' => 9.5, 'max_allowed' => 8],
        ], $overrides));
    }

    #[Test]
    public function export_requires_planner_and_shift_settings_permission(): void
    {
        $this->actingAsUserWith([]);
        $this->get(route('shift-rules.violations.export'))->assertForbidden();

        // Nur "can plan shifts" ohne Schichteinstellungen-Recht reicht nicht (wie die Liste)
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $this->get(route('shift-rules.violations.export'))->assertForbidden();
    }

    #[Test]
    public function export_downloads_with_period_in_filename_and_defaults_to_current_month(): void
    {
        Excel::fake();
        $this->planner();

        $this->get(route('shift-rules.violations.export'))->assertOk();

        Excel::assertDownloaded(sprintf(
            'verstoesse_%s_bis_%s.xlsx',
            Carbon::today()->startOfMonth()->toDateString(),
            Carbon::today()->endOfMonth()->toDateString()
        ));
    }

    #[Test]
    public function export_applies_period_status_severity_and_craft_filters(): void
    {
        Excel::fake();
        $this->planner();
        $rule = ShiftRule::factory()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 8]);
        $craft = Craft::factory()->create();
        $inCraft = User::factory()->create();
        $inCraft->assignedCrafts()->attach($craft->id);
        $other = User::factory()->create();

        $match = $this->violation($inCraft, $rule, ['severity' => 'error', 'violation_date' => '2026-03-10']);
        $this->violation($inCraft, $rule, ['severity' => 'warning', 'violation_date' => '2026-03-11']);   // Schwere
        $this->violation($inCraft, $rule, ['severity' => 'error', 'violation_date' => '2026-04-01']);     // Zeitraum
        $this->violation($inCraft, $rule, ['severity' => 'error', 'violation_date' => '2026-03-12', 'status' => 'ignored']); // Status
        $this->violation($other, $rule, ['severity' => 'error', 'violation_date' => '2026-03-13']);       // Gewerk

        $this->get(route('shift-rules.violations.export', [
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'severity' => 'error',
            'status' => 'active',
            'craft_id' => [$craft->id],
        ]))->assertOk();

        Excel::assertDownloaded(
            'verstoesse_2026-03-01_bis_2026-03-31.xlsx',
            function (ShiftRuleViolationsExcelExport $export) use ($match): bool {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $match->id;
            }
        );
    }

    #[Test]
    public function export_has_bold_headings_and_formats_measured_value_like_the_ui(): void
    {
        $this->planner();
        $rule = ShiftRule::factory()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 8]);
        $user = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Export']);
        $craft = Craft::factory()->create(['name' => 'Technik']);
        $user->assignedCrafts()->attach($craft->id);
        $violation = $this->violation($user, $rule, [
            'severity' => 'error',
            'violation_date' => '2026-03-10',
            'violation_data' => ['planned_hours' => 9.5, 'max_allowed' => 8],
        ]);

        $export = new ShiftRuleViolationsExcelExport(
            app(ShiftRuleViolationRepository::class),
            ['date_from' => '2026-03-01', 'date_to' => '2026-03-31', 'status' => 'active'],
            'desc',
            'de'
        );

        $headings = $export->headings();
        $this->assertCount(15, $headings);
        $this->assertSame(__('Date', [], 'de'), $headings[0]);
        $this->assertSame(__('Measured value', [], 'de'), $headings[5]);
        $this->assertArrayHasKey(1, $export->styles(new \PhpOffice\PhpSpreadsheet\Spreadsheet()->getActiveSheet()));

        $row = $export->map($export->query()->first());
        $this->assertSame('10.03.2026', $row[0]);
        $this->assertSame('Anna Export', $row[1]);
        $this->assertSame('Technik', $row[2]);
        $this->assertSame((new ViolationMeasureFormatter('de'))->format($violation->fresh()->load('shiftRule')), $row[5]);
        $this->assertStringContainsString('9,5 h', $row[5]);
        $this->assertStringContainsString('8 h', $row[5]);
        $this->assertSame(__('Error', [], 'de'), $row[6]);
        $this->assertSame(__('Active', [], 'de'), $row[7]);
    }

    #[Test]
    public function export_reads_large_result_sets_in_chunks_within_a_query_budget(): void
    {
        $this->planner();
        $rule = ShiftRule::factory()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 8]);
        $user = User::factory()->create();

        $rows = [];
        for ($i = 0; $i < 1200; $i++) {
            $rows[] = [
                'shift_rule_id' => $rule->id,
                'shift_id' => null,
                'user_id' => $user->id,
                'violation_date' => '2026-03-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                'violation_data' => json_encode(['planned_hours' => 9, 'max_allowed' => 8]),
                'severity' => 'warning',
                'status' => 'active',
                'is_manual' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table('shift_rule_violations')->insert($chunk);
        }

        $export = new ShiftRuleViolationsExcelExport(
            app(ShiftRuleViolationRepository::class),
            ['date_from' => '2026-03-01', 'date_to' => '2026-03-31', 'status' => 'active'],
            'desc',
            'de'
        );

        DB::flushQueryLog();
        DB::enableQueryLog();
        $mapped = 0;
        // Laravel Excel liest FromQuery-Exporte chunkweise (chunkSize) und ruft map() je Zeile
        $export->query()->chunk($export->chunkSize(), function ($violations) use ($export, &$mapped): void {
            foreach ($violations as $violation) {
                $export->map($violation);
                $mapped++;
            }
        });
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1200, $mapped);
        // 3 Chunks à (1 Hauptquery + 9 Eager-Loads) — kein N+1 je Zeile
        $this->assertLessThan(40, $queries, "Export brauchte {$queries} Queries für 1200 Zeilen");
    }


    #[Test]
    public function export_rejects_periods_longer_than_one_year(): void
    {
        $this->planner();

        $this->getJson(route('shift-rules.violations.export', [
            'date_from' => '2025-01-01',
            'date_to' => '2026-01-03',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    #[Test]
    public function violation_filters_validate_referenced_ids(): void
    {
        $this->planner();

        $this->getJson(route('shift-rules.violations.export', [
            'user_id' => 999999,
            'shift_rule_id' => 999999,
            'craft_id' => [999999],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'shift_rule_id', 'craft_id.0']);
    }
}
