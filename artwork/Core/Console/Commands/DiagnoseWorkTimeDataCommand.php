<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Nur lesend: listet Altdaten, die Zeitkonto und Überstunden verfälschen können (aus Fehlern vor 10/2026).
 * Ändert nichts – Grundlage für eine bewusste Bereinigung bzw. für „Neu buchen“ in den Arbeitszeiten.
 * Gezählt wird immer vollständig; --limit begrenzt nur die gelisteten Zeilen je Prüfung.
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

    private const DAILY_NAME_LIKE = 'daily\_work\_time\_booking\_%';

    private const ADJUSTMENT_NAME_LIKE = 'adjustment\_work\_time\_change\_request\_%';

    /** Bekannte Buchungsnamen außer Tages- und Korrekturzeilen (WorkTimeBookingController::store). */
    private const KNOWN_NAMES = ['manual_booking'];

    protected $signature = 'artwork:work-time:diagnose {--limit=50 : Höchstzahl gelisteter Zeilen je Prüfung}';

    protected $description = 'List legacy data that can falsify time accounts and overtime (read-only)';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $findings = 0;

        $findings += $this->report(
            'Badge (users.work_time_balance) ≠ Summe der Buchungen − Auszahlungen (je Person)',
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
                ->orderBy('users.id'),
            $limit
        );

        $findings += $this->report(
            'Doppelte Tagesbuchungen am selben Tag (parallele Nachtläufe; zählen im Konto mehrfach, '
                . '„Neu buchen“ korrigiert nur die älteste)',
            ['user_id', 'booking_day', 'anzahl', 'summe', 'erste_zeile'],
            DB::table('work_time_bookings')
                ->selectRaw(
                    'user_id, booking_day, COUNT(*) as anzahl, SUM(work_time_balance_change) as summe, '
                    . 'MIN(id) as erste_zeile'
                )
                ->where('name', 'like', self::DAILY_NAME_LIKE)
                ->groupBy('user_id', 'booking_day')
                ->havingRaw('COUNT(*) > 1')
                ->orderBy('user_id')
                ->orderBy('booking_day'),
            $limit
        );

        $findings += $this->report(
            'Tageszeilen mit booker_id oder Kommentar (früher von manueller Buchung überschrieben – '
                . 'der Nachtlauf/„Neu buchen“ ersetzt den manuellen Wert)',
            ['id', 'user_id', 'booking_day', 'booker_id', 'work_time_balance_change', 'comment'],
            DB::table('work_time_bookings')
                ->select(['id', 'user_id', 'booking_day', 'booker_id', 'work_time_balance_change', 'comment'])
                ->where('name', 'like', self::DAILY_NAME_LIKE)
                ->where(fn (Builder $query) => $query
                    ->whereNotNull('booker_id')
                    ->orWhere(fn (Builder $inner) => $inner->whereNotNull('comment')->where('comment', '<>', '')))
                ->orderBy('id'),
            $limit
        );

        $findings += $this->report(
            'Tageszeilen, deren Name ein anderes Datum trägt als booking_day (zählen als Zusatzbuchung)',
            ['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'],
            DB::table('work_time_bookings')
                ->select(['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'])
                ->where('name', 'like', self::DAILY_NAME_LIKE)
                ->whereRaw("name <> CONCAT('daily_work_time_booking_', DATE_FORMAT(booking_day, '%Y-%m-%d'))")
                ->orderBy('id'),
            $limit
        );

        $findings += $this->report(
            'Buchungen in der Zukunft (booking_day nach heute)',
            ['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'],
            DB::table('work_time_bookings')
                ->select(['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'])
                ->where('booking_day', '>', now()->toDateString())
                ->orderBy('booking_day')
                ->orderBy('id'),
            $limit
        );

        $findings += $this->report(
            'Buchungen mit unbekanntem oder fehlendem Namen (je Name)',
            ['name', 'anzahl', 'personen', 'summe', 'erster_tag', 'letzter_tag'],
            DB::table('work_time_bookings')
                ->selectRaw(
                    "COALESCE(name, '(ohne Namen)') as name, COUNT(*) as anzahl, COUNT(DISTINCT user_id) as personen, "
                    . 'SUM(work_time_balance_change) as summe, MIN(booking_day) as erster_tag, '
                    . 'MAX(booking_day) as letzter_tag'
                )
                ->where(fn (Builder $query) => $query
                    ->whereNull('name')
                    ->orWhere(fn (Builder $inner) => $inner
                        ->where('name', 'not like', self::DAILY_NAME_LIKE)
                        ->where('name', 'not like', self::ADJUSTMENT_NAME_LIKE)
                        ->whereNotIn('name', self::KNOWN_NAMES)))
                ->groupBy('name')
                ->orderByDesc('anzahl'),
            $limit
        );

        $findings += $this->report(
            'Tageszeilen mit Saldo ≠ Ist − Soll (frühere Krank-Logik)',
            self::BOOKING_COLUMNS,
            DB::table('work_time_bookings')
                ->select(self::BOOKING_COLUMNS)
                ->where('name', 'like', self::DAILY_NAME_LIKE)
                ->whereRaw('work_time_balance_change <> worked_hours - wanted_working_hours')
                ->orderBy('id'),
            $limit
        );

        $findings += $this->report(
            'Alte Korrekturbuchungen aus Zeitänderungen (am Genehmigungstag gebucht; '
                . 'werden beim Neu buchen berücksichtigt)',
            ['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'],
            DB::table('work_time_bookings')
                ->select(['id', 'user_id', 'booking_day', 'name', 'work_time_balance_change'])
                ->where('name', 'like', self::ADJUSTMENT_NAME_LIKE)
                ->orderBy('id'),
            $limit
        );

        $findings += $this->report(
            'Schichtzuweisungen länger als 24 h (früherer Fehler bei individuellen Zeiten)',
            self::PIVOT_COLUMNS,
            DB::table('shift_workers')
                ->select(self::PIVOT_COLUMNS)
                ->whereNull('deleted_at')
                ->whereRaw(self::LONGER_THAN_A_DAY)
                ->orderBy('id'),
            $limit
        );

        $findings += $this->report(
            'Individuelle Zeiten länger als 24 h (Enddatum nach Bearbeitung nicht mitgezogen)',
            ['id', 'timeable_type', 'timeable_id', 'start_date', 'start_time', 'end_date', 'end_time'],
            DB::table('individual_times')
                ->select(['id', 'timeable_type', 'timeable_id', 'start_date', 'start_time', 'end_date', 'end_time'])
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->whereRaw(self::LONGER_THAN_A_DAY)
                ->orderBy('id'),
            $limit
        );

        $this->newLine();
        $findings === 0
            ? $this->info('Keine Auffälligkeiten gefunden.')
            : $this->warn("{$findings} auffällige Datensätze gefunden (je Prüfung höchstens {$limit} gelistet).");

        return self::SUCCESS;
    }

    /**
     * Zählt alle Treffer (ohne Limit) und listet höchstens $limit davon.
     *
     * @param array<int, string> $columns
     */
    private function report(string $title, array $columns, Builder $query, int $limit): int
    {
        $total = DB::query()->fromSub((clone $query)->reorder(), 'findings')->count();
        $rows = $total === 0
            ? collect()
            : $query->limit($limit)->get()->map(fn (object $row): array => array_map(
                fn (string $column): string => (string) ($row->{$column} ?? ''),
                $columns
            ));

        $this->newLine();
        $this->line("<options=bold>{$title}</>: {$total}");
        if ($rows->isNotEmpty()) {
            $this->table($columns, $rows->all());
            if ($total > $rows->count()) {
                $this->line('… ' . ($total - $rows->count()) . ' weitere (--limit erhöhen)');
            }
        }

        return $total;
    }
}
