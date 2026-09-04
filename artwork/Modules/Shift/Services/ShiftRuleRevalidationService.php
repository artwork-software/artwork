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
 */
class ShiftRuleRevalidationService
{
    public const MIN_DAYS_AHEAD = 14;
    public const MAX_MONTHS_AHEAD = 12;

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
     */
    public function revalidateForUsers(array $userIds): void
    {
        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));
        if ($userIds === []) {
            return;
        }

        [$from, $to] = $this->rangeForUsers($userIds);

        RevalidateShiftRulesJob::dispatch($userIds, $from->toDateString(), $to->toDateString())->afterCommit();
    }

    /**
     * @param array<int, int> $userIds
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rangeForUsers(array $userIds): array
    {
        $today = Carbon::today();
        $to = $today->copy()->addDays(self::MIN_DAYS_AHEAD);
        $cap = $today->copy()->addMonths(self::MAX_MONTHS_AHEAD);

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

        return [$today, $to->min($cap)];
    }
}
