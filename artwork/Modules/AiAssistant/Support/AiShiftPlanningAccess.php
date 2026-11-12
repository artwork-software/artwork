<?php

namespace Artwork\Modules\AiAssistant\Support;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;

/**
 * Rechte der Schichtplanungs-Endpunkte (ai-shift-planning).
 *
 * Unter `auth:api` liefert `can()` fuer jedes Hausrecht still `false` (HANDOVER_core §2), deshalb
 * `checkPermissionTo(…, 'web')`. Die Rollenabkuerzung bildet den gewollten Admin-Durchgriff von
 * `Gate::before` nach — dieselbe Wirkung wie das Gate `plan-shifts` in der Oberflaeche.
 */
final class AiShiftPlanningAccess
{
    public static function mayPlanShifts(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->checkPermissionTo(PermissionEnum::SHIFT_PLANNER->value, 'web');
    }

    public static function mayViewShiftPlan(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->checkPermissionTo(PermissionEnum::VIEW_SHIFT_PLAN->value, 'web');
    }
}
