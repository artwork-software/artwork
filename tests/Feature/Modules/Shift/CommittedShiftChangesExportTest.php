<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Exports\CommittedShiftChangesExcelExport;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Excel-Export der Änderungsübersicht (committed-shift-changes.export): Rechte, Filter wie die Liste,
 * Kopfzeile/Spalten, Query-Budget.
 */
final class CommittedShiftChangesExportTest extends FeatureTestCase
{
    private function makeChange(array $attributes = []): CommittedShiftChange
    {
        return CommittedShiftChange::query()->forceCreate(array_merge([
            'subject_type' => 'Shift',
            'subject_id' => 1,
            'change_type' => 'updated',
            'field_changes' => [],
            'changed_at' => now(),
            'acknowledged_at' => null,
        ], $attributes));
    }

    #[Test]
    public function export_requires_approver_gate(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('committed-shift-changes.export'))->assertForbidden();
    }

    #[Test]
    public function export_applies_craft_status_worker_type_and_period_filters(): void
    {
        Excel::fake();
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $otherCraft = Craft::factory()->create();
        $anna = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Intern', 'is_freelancer' => false]);

        $match = $this->makeChange([
            'craft_id' => $craft->id,
            'change_type' => 'user_added_to_shift',
            'affected_user_type' => User::class,
            'affected_user_id' => $anna->id,
            'changed_at' => '2026-03-10 10:00:00',
        ]);
        $this->makeChange(['craft_id' => $craft->id, 'acknowledged_at' => now(), 'changed_at' => '2026-03-11 10:00:00']); // Status
        $this->makeChange(['craft_id' => $otherCraft->id, 'changed_at' => '2026-03-12 10:00:00']);                       // Gewerk
        $this->makeChange(['craft_id' => $craft->id, 'changed_at' => '2026-04-02 10:00:00']);                            // Zeitraum

        $this->get(route('committed-shift-changes.export', [
            'craft_id' => $craft->id,
            'filter' => 'open',
            'worker_type' => 'internal',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]))->assertOk();

        Excel::assertDownloaded(
            'aenderungen_2026-03-01_bis_2026-03-31.xlsx',
            function (CommittedShiftChangesExcelExport $export) use ($match): bool {
                $ids = $export->query()->pluck('id')->all();

                // Zeitänderung ohne betroffene Person bleibt (wie in der Liste) in beiden Sichten;
                // die Zuweisung von Anna ist der personenbezogene Treffer.
                return in_array($match->id, $ids, true) && count($ids) === 1;
            }
        );
    }

    #[Test]
    public function export_rows_describe_the_change_like_the_list(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create(['abbreviation' => 'TE', 'name' => 'Technik']);
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-10',
            'start' => '09:00:00',
            'end' => '17:00:00',
        ]);
        $change = $this->makeChange([
            'craft_id' => $craft->id,
            'shift_id' => $shift->id,
            'change_type' => 'user_removed_from_shift',
            'field_changes' => ['assignment' => ['user_name' => 'Anna Muster', 'before_label' => '09:00 - 17:00', 'after_label' => 'free']],
            'changed_by_user_id' => $admin->id,
            'changed_at' => '2026-03-10 10:00:00',
            'acknowledged_at' => '2026-03-11 08:00:00',
            'acknowledged_by_user_id' => $admin->id,
        ]);

        $export = new CommittedShiftChangesExcelExport(
            CommittedShiftChange::query()->whereKey($change->id),
            'de'
        );

        $headings = $export->headings();
        $this->assertCount(13, $headings);
        $this->assertSame(__('Timestamp', [], 'de'), $headings[0]);
        $this->assertSame(__('Kind of change', [], 'de'), $headings[6]);

        $row = $export->map($export->query()->first());
        $this->assertSame('10.03.2026 10:00', $row[0]);
        $this->assertSame('10.03.2026', $row[1]);
        $this->assertSame('09:00 – 17:00', $row[2]);
        $this->assertSame('TE', $row[4]);
        $this->assertSame('Anna Muster', $row[5]);
        $this->assertStringContainsString('Anna Muster', $row[6]);
        $this->assertStringNotContainsString('{0}', $row[6]);
        $this->assertSame('09:00 - 17:00', $row[7]);
        $this->assertSame(__('Approval granted', [], 'de'), $row[10]);
        $this->assertSame('11.03.2026 08:00', $row[12]);
    }

    #[Test]
    public function export_reads_large_change_lists_in_chunks_within_a_query_budget(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $shift = Shift::factory()->create(['craft_id' => $craft->id, 'start_date' => '2026-03-10', 'end_date' => '2026-03-10']);

        $rows = [];
        for ($i = 0; $i < 1100; $i++) {
            $rows[] = [
                'craft_id' => $craft->id,
                'shift_id' => $shift->id,
                'subject_type' => 'Shift',
                'subject_id' => $shift->id,
                'change_type' => 'updated',
                'field_changes' => json_encode(['start' => ['old' => '09:00', 'new' => '10:00']]),
                'changed_at' => now()->subMinutes($i),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table('committed_shift_changes')->insert($chunk);
        }

        $export = new CommittedShiftChangesExcelExport(CommittedShiftChange::query()->where('craft_id', $craft->id), 'de');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $mapped = 0;
        $export->query()->chunk($export->chunkSize(), function ($changes) use ($export, &$mapped): void {
            foreach ($changes as $change) {
                $export->map($change);
                $mapped++;
            }
        });
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1100, $mapped);
        // 3 Chunks à (1 Hauptquery + 6 Eager-Loads) — kein N+1 je Zeile
        $this->assertLessThan(30, $queries, "Export brauchte {$queries} Queries für 1100 Zeilen");
    }


    #[Test]
    public function export_rejects_periods_longer_than_one_year(): void
    {
        $this->actingAsAdmin();

        $this->getJson(route('committed-shift-changes.export', [
            'date_from' => '2025-01-01',
            'date_to' => '2026-01-03',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    /**
     * @return array<int, int>
     */
    private function exportedIds(string $filename): array
    {
        $ids = [];
        Excel::assertDownloaded($filename, function (CommittedShiftChangesExcelExport $export) use (&$ids): bool {
            $ids = $export->query()->pluck('id')->map(fn ($id) => (int) $id)->all();

            return true;
        });

        return $ids;
    }

    #[Test]
    public function export_without_period_is_limited_to_the_current_month(): void
    {
        Excel::fake();
        $this->actingAsAdmin();
        $monthStart = now()->startOfMonth();
        $inMonth = $this->makeChange(['changed_at' => $monthStart->copy()->addDays(2)->setTime(10, 0)]);
        $previousMonth = $this->makeChange(['changed_at' => $monthStart->copy()->subDay()->setTime(10, 0)]);

        $this->get(route('committed-shift-changes.export'))->assertOk();

        $ids = $this->exportedIds(sprintf(
            'aenderungen_%s_bis_%s.xlsx',
            $monthStart->toDateString(),
            now()->endOfMonth()->toDateString()
        ));
        $this->assertContains($inMonth->id, $ids);
        $this->assertNotContains($previousMonth->id, $ids, 'Export ohne Zeitraum ist nicht auf den Monat begrenzt');
    }

    #[Test]
    public function export_with_a_single_bound_is_limited_to_one_year(): void
    {
        Excel::fake();
        $this->actingAsAdmin();
        $inside = $this->makeChange(['changed_at' => '2020-06-01 10:00:00']);
        $lastDay = $this->makeChange(['changed_at' => '2021-01-01 10:00:00']);
        $afterCap = $this->makeChange(['changed_at' => '2021-01-02 10:00:00']);
        $beforeStart = $this->makeChange(['changed_at' => '2019-12-31 10:00:00']);

        // Nur "von": bis = von + 366 Tage
        $this->get(route('committed-shift-changes.export', ['date_from' => '2020-01-01']))->assertOk();
        $ids = $this->exportedIds('aenderungen_2020-01-01_bis_2021-01-01.xlsx');
        $this->assertContains($inside->id, $ids);
        $this->assertContains($lastDay->id, $ids);
        $this->assertNotContains($afterCap->id, $ids, 'Export mit nur "von" ist nach oben unbegrenzt');
        $this->assertNotContains($beforeStart->id, $ids);

        // Nur "bis": von = bis − 366 Tage (2020 ist ein Schaltjahr)
        $this->get(route('committed-shift-changes.export', ['date_to' => '2021-01-01']))->assertOk();
        $ids = $this->exportedIds('aenderungen_2020-01-01_bis_2021-01-01.xlsx');
        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($beforeStart->id, $ids, 'Export mit nur "bis" ist nach unten unbegrenzt');
        $this->assertNotContains($afterCap->id, $ids);
    }
}
