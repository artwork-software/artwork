<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;

class BudgetManagementCostUnitPolicy
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

    public function create(User $user): bool
    {
        return $user->canAny(
            [
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }

    public function delete(User $user): bool
    {
        return $user->canAny(
            [
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }
}
