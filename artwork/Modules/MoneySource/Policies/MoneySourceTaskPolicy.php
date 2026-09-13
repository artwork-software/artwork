<?php

namespace Artwork\Modules\MoneySource\Policies;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Finanzierungsaufgaben (FIN-01):
 * - Anlegen/Löschen nur, wer die Finanzierungsquelle bearbeiten darf (MoneySourcePolicy::update).
 * - Erledigen/Rückgängig: Zugewiesene ODER wer die Quelle bearbeiten darf.
 * - Liste: wer die Quelle sehen darf (MoneySourcePolicy::view).
 * Admins passieren via Gate::before.
 */
class MoneySourceTaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, MoneySource $moneySource): bool
    {
        return $user->can('view', $moneySource);
    }

    public function view(User $user, MoneySourceTask $moneySourceTask): bool
    {
        return $this->isAssignee($user, $moneySourceTask)
            || $user->can('view', $moneySourceTask->money_source);
    }

    public function create(User $user, MoneySource $moneySource): bool
    {
        return $user->can('update', $moneySource);
    }

    public function complete(User $user, MoneySourceTask $moneySourceTask): bool
    {
        return $this->isAssignee($user, $moneySourceTask)
            || $this->canUpdateSource($user, $moneySourceTask);
    }

    public function delete(User $user, MoneySourceTask $moneySourceTask): bool
    {
        return $this->canUpdateSource($user, $moneySourceTask);
    }

    private function canUpdateSource(User $user, MoneySourceTask $moneySourceTask): bool
    {
        $moneySource = $moneySourceTask->money_source;

        return $moneySource !== null && $user->can('update', $moneySource);
    }

    private function isAssignee(User $user, MoneySourceTask $moneySourceTask): bool
    {
        return $moneySourceTask->money_source_task_users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
