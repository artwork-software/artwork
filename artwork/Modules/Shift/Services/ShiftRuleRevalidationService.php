<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Jobs\RevalidateShiftRulesJob;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;

/**
 * Stößt die Neuprüfung der Regeln an, wenn sich Regeln, deren Vertragszuordnung oder die
 * Vertragszuweisung einer Person ändern. Der 5-Minuten-Cron sieht nur die nächsten 14 Tage —
 * Änderungen müssen aber auch für bereits geplante Schichten weiter in der Zukunft gelten.
 *
 * Zeitraum: heute bis max(Ende der zukünftigen Schichten der Personen, heute + 14 Tage),
 * gedeckelt auf heute + 12 Monate. Die Prüfung läuft als Queue-Job (RevalidateShiftRulesJob),
 * dispatcht nach Commit der laufenden Transaktion.
 *
 * Rückwirkende Vertragszeiträume (Historie seit 2026-09): Optional ein Startdatum in der
 * Vergangenheit – dann beginnt die Prüfung dort (gedeckelt auf heute − 12 Monate), damit bereits
 * festgeschriebene Schichten im geänderten Zeitraum neu bewertet werden.
 */
class ShiftRuleRevalidationService
{
    public const MIN_DAYS_AHEAD = 14;
    public const MAX_MONTHS_AHEAD = 12;
    public const MAX_MONTHS_BACK = 12;

    /**
     * Personen aller angegebenen Verträge neu prüfen.
     *
     * @param array<int, int|string> $contractIds
     */
    public function revalidateForContracts(array $contractIds): void
    {
        $contractIds = array_values(array_unique(array_map('intval', array_filter($contractIds))));
        if ($contractIds === []) {
            return;
        }

        $userIds = UserContractAssign::query()
            ->whereIn('user_contract_id', $contractIds)
            ->pluck('user_id')
            ->all();

        $this->revalidateForUsers($userIds);
    }

    /**
     * @param array<int, int|string> $userIds
     * @param Carbon|null $from Frühestes betroffenes Datum (Vergangenheit erlaubt); null = heute
     */
    public function revalidateForUsers(array $userIds, ?Carbon $from = null): void
    {
        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));
        if ($userIds === []) {
            return;
        }

        [$from, $to] = $this->rangeForUsers($userIds, $from);

        RevalidateShiftRulesJob::dispatch($userIds, $from->toDateString(), $to->toDateString())->afterCommit();
    }

    /**
     * @param array<int, int> $userIds
     * @param Carbon|null $from Startdatum vor heute erweitert den Zeitraum nach hinten (Deckel 12 Monate)
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rangeForUsers(array $userIds, ?Carbon $from = null): array
    {
        $today = Carbon::today();
        $to = $today->copy()->addDays(self::MIN_DAYS_AHEAD);
        $cap = $today->copy()->addMonths(self::MAX_MONTHS_AHEAD);

        $start = $today->copy();
        if ($from !== null && $from->copy()->startOfDay()->lt($today)) {
            $start = $from->copy()->startOfDay()->max($today->copy()->subMonths(self::MAX_MONTHS_BACK));
        }

        // Spätestes Ende zukünftiger Schichten der Personen: Schicht-Ende ODER personenindividuelles
        // Pivot-Ende (shift_workers.end_date); gelöschte Schichten/Zuweisungen bleiben außen vor.
        $latestEnd = ShiftWorker::query()
            ->join('shifts', 'shifts.id', '=', 'shift_workers.shift_id')
            ->where('shift_workers.employable_type', User::class)
            ->whereIn('shift_workers.employable_id', $userIds)
            ->whereNull('shift_workers.deleted_at')
            ->whereNull('shifts.deleted_at')
            ->where(function ($query) use ($today): void {
                $query->whereDate('shifts.end_date', '>=', $today->toDateString())
                    ->orWhereDate('shift_workers.end_date', '>=', $today->toDateString());
            })
            ->selectRaw('MAX(GREATEST(COALESCE(shift_workers.end_date, shifts.end_date), shifts.end_date)) as latest_end')
            ->value('latest_end');

        if ($latestEnd) {
            $to = $to->max(Carbon::parse($latestEnd)->startOfDay());
        }

        return [$start, $to->min($cap)];
    }
}
