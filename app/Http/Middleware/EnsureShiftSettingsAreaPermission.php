<?php

namespace App\Http\Middleware;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShiftSettingsAreaPermission
{
    public function __construct(private readonly ShiftSettings $shiftSettings)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $area,
        string $access = 'view'
    ): Response
    {
        $user = $request->user();
        abort_unless($user?->can(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value), Response::HTTP_FORBIDDEN);

        if (!$this->shiftSettings->granular_permissions_enabled) {
            return $next($request);
        }

        $permission = $this->permissionFor($area, $access);
        abort_if($permission === null, Response::HTTP_FORBIDDEN);

        $hasPermission = $user->can($permission->value);
        if ($access === 'view') {
            $editPermission = $this->permissionFor($area, 'edit');
            $hasPermission = $hasPermission || ($editPermission !== null && $user->can($editPermission->value));
        }

        abort_unless($hasPermission, Response::HTTP_FORBIDDEN);

        return $next($request);
    }

    private function permissionFor(string $area, string $access): ?PermissionEnum
    {
        return match ([$area, $access]) {
            ['general', 'view'] => PermissionEnum::SHIFT_SETTINGS_GENERAL_VIEW,
            ['general', 'edit'] => PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT,
            ['day-services', 'view'] => PermissionEnum::SHIFT_SETTINGS_DAY_SERVICES_VIEW,
            ['day-services', 'edit'] => PermissionEnum::SHIFT_SETTINGS_DAY_SERVICES_EDIT,
            ['work-time-patterns', 'view'] => PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_VIEW,
            ['work-time-patterns', 'edit'] => PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_EDIT,
            ['user-contracts', 'view'] => PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_VIEW,
            ['user-contracts', 'edit'] => PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_EDIT,
            ['shift-groups', 'view'] => PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_VIEW,
            ['shift-groups', 'edit'] => PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_EDIT,
            ['shift-templates', 'view'] => PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_VIEW,
            ['shift-templates', 'edit'] => PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_EDIT,
            ['rules', 'view'] => PermissionEnum::SHIFT_SETTINGS_RULES_VIEW,
            ['rules', 'edit'] => PermissionEnum::SHIFT_SETTINGS_RULES_EDIT,
            default => null,
        };
    }
}
