<?php

namespace Artwork\Modules\Shift\Jobs;

use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Regelprüfung für eine Menge Personen im Zeitraum [$from, $to] (Y-m-d) im Hintergrund —
 * ausgelöst von Regel-/Vertragsänderungen (ShiftRuleRevalidationService). Je Person läuft
 * ShiftRuleService::validateRulesForUser mit gebatchtem Datenkontext; Verstöße jenseits der
 * 14-Tage-Cron-Sicht werden so erzeugt bzw. automatisch aufgelöst.
 *
 * Nach Änderungen an diesem Job: `queue:restart` (Worker halten alten Code).
 */
class RevalidateShiftRulesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    /**
     * @param array<int, int> $userIds
     */
    public function __construct(
        public readonly array $userIds,
        public readonly string $from,
        public readonly string $to,
    ) {
    }

    public function handle(ShiftRuleService $shiftRuleService): void
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->startOfDay();
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        User::query()
            ->whereIn('id', $this->userIds)
            ->orderBy('id')
            ->chunkById(50, function ($users) use ($shiftRuleService, $from, $to): void {
                foreach ($users as $user) {
                    $shiftRuleService->validateRulesForUser($user, $from->copy(), $to->copy());
                }
            });
    }
}
