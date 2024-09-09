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
    ];

    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
        private readonly UserService $userService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (
            ($user = $this->userService->getAuthUser()) === null ||
            $user->hasRole(RoleEnum::ARTWORK_ADMIN->value) ||
            !in_array($request->getRequestUri(), array_keys(self::ROUTE_SETTING_MAPPING)) ||
            $this->moduleSettingsService->isModuleVisible(self::ROUTE_SETTING_MAPPING[$request->getRequestUri()])
        ) {
            return $next($request);
        }

        throw new UnauthorizedException(401);
    }
}
