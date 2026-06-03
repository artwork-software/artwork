<?php

namespace Artwork\Modules\ModuleSettings\Http\Middleware;

use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ModuleSettingsMiddleware
{
    private const ROUTE_SETTING_MAPPING = [
        '/projects' => 'projects',
        '/calendar/view' => 'room_assignment',
        '/shifts/view' => 'shift_plan',
        '/inventory-management' => 'inventory',
        '/inventory-management/scheduling' => 'inventory',
        '/tasks/own' => 'tasks',
        '/money_sources' => 'sources_of_funding',
        '/users' => 'users',
        '/contracts/view' => 'contracts',
        '/planning-event-calendar' => 'planning_calendar',
        '/bi/dashboard' => 'business_intelligence',
    ];

    /** @var string[] Settings where even admins are blocked when the module is disabled */
    private const NO_ADMIN_BYPASS = [
        'planning_calendar',
    ];

    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
        private readonly UserService $userService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $this->userService->getAuthUser();

        if ($user === null) {
            return $next($request);
        }

        $requestUri = $request->getRequestUri();

        if (!in_array($requestUri, array_keys(self::ROUTE_SETTING_MAPPING))) {
            return $next($request);
        }

        $setting = self::ROUTE_SETTING_MAPPING[$requestUri];

        if ($this->moduleSettingsService->isModuleVisible($setting)) {
            return $next($request);
        }

        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value) && !in_array($setting, self::NO_ADMIN_BYPASS)) {
            return $next($request);
        }

        throw new UnauthorizedException(401);
    }
}
