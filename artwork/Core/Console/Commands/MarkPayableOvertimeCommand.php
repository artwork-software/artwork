<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Illuminate\Console\Command;
use Throwable;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;

/**
 * Nächtlicher Recompute der Überstunden je User (Kontoprinzip, OvertimeLedger): kippt offene Einträge mit
 * abgelaufener Frist auf "auszahlbar" (payable), auch ohne neue Zeitbuchung.
 */
class MarkPayableOvertimeCommand extends Command
{
    protected $signature = 'artwork:mark-payable-overtime';

    protected $description = 'Mark overdue, non-reduced overtime as payable per user';

    public function handle(OvertimeService $service): int
    {
        if (!WorkTimeAccounting::isEnabled()) {
            $this->info('Work time accounting is disabled, skipping.');

            return self::SUCCESS;
        }

        $this->info('Recomputing overtime entries...');

        // Auch Personen, die nicht (mehr) im Dienstplan sind, aber ein Stundenkonto bzw. Überstunden haben:
        // sonst kippen deren Fristen nie auf „auszahlbar“
        $count = 0;
        User::query()
            // geklammert: chunkById hängt "and id > ?" an
            ->where(fn ($query) => $query->where('can_work_shifts', true)->orWhereHas('workTimeBookings'))
            ->chunkById(200, function ($users) use ($service, &$count): void {
                foreach ($users as $user) {
                    // Ein fehlerhafter Datensatz darf die übrigen Personen nicht aufhalten
                    try {
                        $service->recomputeForUser($user);
                        $count++;
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });

        $this->info("Overtime recomputed for {$count} users.");

        return self::SUCCESS;
    }
}
