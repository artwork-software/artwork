<?php

namespace Artwork\Modules\BudgetColumnSetting\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;

class BudgetColumnSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(
            [
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }

    public function update(User $user): bool
    {
        return $user->canAny(
            [
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }
}
