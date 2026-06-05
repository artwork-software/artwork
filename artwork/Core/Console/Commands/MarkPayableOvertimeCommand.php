<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Shift\Services\OvertimeService;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;

/**
 * Markiert nächtlich die überfälligen, nicht abgebauten Überstunden je User als
 * "auszuzahlend" (users.payable_overtime_minutes). DP-18 Stufe 2.
 */
class MarkPayableOvertimeCommand extends Command
{
    protected $signature = 'artwork:mark-payable-overtime';

    protected $description = 'Mark overdue, non-reduced overtime as payable per user';

    public function handle(OvertimeService $service): int
    {
        $this->info('Marking payable overtime...');

        $users = User::query()->where('can_work_shifts', true)->get();
        $count = 0;

        foreach ($users as $user) {
            $result = $service->computeForUser($user);
            $payable = (int) $result['payable_minutes'];

            if ((int) $user->payable_overtime_minutes !== $payable) {
                $user->forceFill(['payable_overtime_minutes' => $payable])->saveQuietly();
            }
            $count++;
        }

        $this->info("Payable overtime updated for {$count} users.");

        return self::SUCCESS;
    }
}
