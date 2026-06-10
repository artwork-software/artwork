<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Illuminate\Console\Command;

/**
 * Nächtlicher Recompute der Überstunden-Einträge je User: kippt offene Einträge mit
 * abgelaufener Frist auf "auszuzahlend" (payable), auch ohne neue Zeitbuchung. DP-18 Stufe 2.
 */
class MarkPayableOvertimeCommand extends Command
{
    protected $signature = 'artwork:mark-payable-overtime';

    protected $description = 'Mark overdue, non-reduced overtime as payable per user';

    public function handle(OvertimeService $service): int
    {
        $this->info('Recomputing overtime entries...');

        $users = User::query()->where('can_work_shifts', true)->get();

        foreach ($users as $user) {
            $service->recomputeForUser($user);
        }

        $this->info("Overtime recomputed for {$users->count()} users.");

        return self::SUCCESS;
    }
}
