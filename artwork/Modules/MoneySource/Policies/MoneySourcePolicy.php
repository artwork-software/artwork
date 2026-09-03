<?php

namespace Artwork\Modules\MoneySource\Policies;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Spiegelt die Frontend-Logik (MoneySources/Show.vue canManage, MoneySourceManagement.vue
 * canWriteOrCompetent): Schreiben darf, wer das globale Recht hat, Ersteller*in ist oder
 * an der Quelle als "write_access"/"competent" eingetragen ist. Admins passieren via Gate::before.
 */
class MoneySourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
            PermissionEnum::MONEY_SOURCE_EDIT_DELETE->value,
        ]);
    }

    public function view(User $user, MoneySource $moneySource): bool
    {
        return $this->viewAny($user) || $this->isMember($user, $moneySource);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
    }

    public function update(User $user, MoneySource $moneySource): bool
    {
        return $user->canAny([
            PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
            PermissionEnum::MONEY_SOURCE_EDIT_DELETE->value,
            PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
        ])
            || $moneySource->creator_id === $user->id
            || $this->isMember($user, $moneySource);
    }

    public function delete(User $user, MoneySource $moneySource): bool
    {
        return $user->canAny([
            PermissionEnum::MONEY_SOURCE_EDIT_DELETE->value,
            PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
        ])
            || $moneySource->creator_id === $user->id;
    }

    private function isMember(User $user, MoneySource $moneySource): bool
    {
        return $moneySource->users()
            ->where('users.id', $user->id)
            ->where(function ($query): void {
                $query->where('money_source_users.write_access', true)
                    ->orWhere('money_source_users.competent', true);
            })
            ->exists();
    }
}
