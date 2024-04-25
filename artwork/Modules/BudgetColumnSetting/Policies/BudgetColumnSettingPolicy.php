<?php

namespace Artwork\Modules\BudgetColumnSetting\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;

class BudgetColumnSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(
            [
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }

    public function update(User $user): bool
    {
        return $user->canAny(
            [
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }
}
