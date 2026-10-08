<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nur lesend: listet Altdaten, die Zeitkonto und Überstunden verfälschen können (aus Fehlern vor 10/2026).
 * Ändert nichts – Grundlage für eine bewusste Bereinigung bzw. für „Neu buchen“ in den Arbeitszeiten.
 */
class DiagnoseWorkTimeDataCommand extends Command
{
    private const BOOKING_COLUMNS = [
        'id', 'user_id', 'booking_day', 'worked_hours', 'wanted_working_hours', 'work_time_balance_change',
    ];

    private const PIVOT_COLUMNS = [
        'id', 'shift_id', 'employable_type', 'employable_id', 'start_date', 'start_time', 'end_date', 'end_time',
    ];

    private const LONGER_THAN_A_DAY =
        'TIMESTAMPDIFF(MINUTE, TIMESTAMP(start_date, start_time), TIMESTAMP(end_date, end_time)) > 1440';

    protected $signature = 'artwork:work-time:diagnose {--limit=50 : Höchstzahl gelisteter Zeilen je Prüfung}';

    protected $description = 'List legacy data that can falsify time accounts and overtime (read-only)';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $findings = 0;

        $findings += $this->report(
            'Zeitkonto ≠ Summe der Buchungen − Auszahlungen',
            ['user_id', 'kontostand', 'buchungen', 'auszahlungen', 'differenz'],
            DB::table('users')
                ->selectRaw(
                    'users.id as user_id, users.work_time_balance as kontostand, '
                    . 'COALESCE(b.total, 0) as buchungen, COALESCE(p.total, 0) as auszahlungen, '
                    . 'users.work_time_balance - COALESCE(b.total, 0) + COALESCE(p.total, 0) as differenz'
                )
                ->leftJoinSub(
                    DB::table('work_time_bookings')
                        ->selectRaw('user_id, SUM(work_time_balance_change) as total')
                        ->groupBy('user_id'),
                    'b',
                    'b.user_id',
                    '=',
                    'users.id'
                )
                ->leftJoinSub(
                    DB::table('overtime_payouts')->selectRaw('user_id, SUM(minutes) as total')->groupBy('user_id'),
                    'p',
                    'p.user_id',
                    '=',
                    'users.id'
                )
                ->whereRaw('users.work_time_balance <> COALESCE(b.total, 0) - COALESCE(p.total, 0)')
                ->limit($limit)
                ->get()
        );

        $findings += $this->report(
            'Doppelte Tagesbuchungen (parallele Nachtläufe)',
            ['user_id', 'name', 'anzahl', 'summe'],
            DB::table('work_time_bookings')
                ->selectRaw('user_id, name, COUNT(*) as anzahl, SUM(work_time_balance_change) as summe')
                ->where('name', 'like', 'daily\_work\_time\_booking\_%')
                ->groupBy('user_id', 'name')
                ->havingRaw('COUNT(*) > 1')
                ->limit($limit)
                ->get()
        );

        $findings += $this->report(
            'Tagesbuchungen mit Saldo ≠ Ist − Soll (frühere Krank-Logik)',
            self::BOOKING_COLUMNS,
            DB::table('work_time_bookings')
                ->select(self::BOOKING_COLUMNS)
                ->where('name', 'like', 'daily\_work\_time\_booking\_%')
                ->whereRaw('work_time_balance_change <> worked_hours - wanted_working_hours')
                ->limit($limit)
                ->get()
        );

        $findings += $this->report(
            'Alte Korrekturbuchungen aus Zeitänderungen (am Genehmigungstag gebucht; '
                . 'werden beim Neu buchen berücksichtigt)',
            ['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'],
            DB::table('work_time_bookings')
                ->select(['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'])
                ->where('name', 'like', 'adjustment\_work\_time\_change\_request\_%')
                ->limit($limit)
                ->get()
        );

        $findings += $this->report(
            'Schichtzuweisungen länger als 24 h (früherer Fehler bei individuellen Zeiten)',
            self::PIVOT_COLUMNS,
            DB::table('shift_workers')
                ->select(self::PIVOT_COLUMNS)
                ->whereNull('deleted_at')
                ->whereRaw(self::LONGER_THAN_A_DAY)
                ->limit($limit)
                ->get()
        );

        $findings += $this->report(
            'Individuelle Zeiten länger als 24 h (Enddatum nach Bearbeitung nicht mitgezogen)',
            ['id', 'timeable_type', 'timeable_id', 'start_date', 'start_time', 'end_date', 'end_time'],
            DB::table('individual_times')
                ->select(['id', 'timeable_type', 'timeable_id', 'start_date', 'start_time', 'end_date', 'end_time'])
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->whereRaw(self::LONGER_THAN_A_DAY)
                ->limit($limit)
                ->get()
        );

        $this->newLine();
        $findings === 0
            ? $this->info('Keine Auffälligkeiten gefunden.')
            : $this->warn("{$findings} auffällige Datensätze gefunden (je Prüfung höchstens {$limit} gelistet).");

        return self::SUCCESS;
    }

    /**
     * @param array<int, string> $columns
     * @param iterable<object> $rows
     */
    private function report(string $title, array $columns, iterable $rows): int
    {
        $rows = collect($rows)->map(fn (object $row): array => array_map(
            fn (string $column): string => (string) ($row->{$column} ?? ''),
            $columns
        ));

        $this->newLine();
        $this->line("<options=bold>{$title}</>: {$rows->count()}");
        if ($rows->isNotEmpty()) {
            $this->table($columns, $rows->all());
        }

        return $rows->count();
    }
}
