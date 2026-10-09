<?php

namespace Artwork\Modules\AppApi\Http\Middleware;

use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Modul-Schalter für die App-API: das Web prüft sie über ModuleSettingsMiddleware (Pfad-Präfixe),
 * der api.app-Stack hat diese Middleware nicht. Die App-Routen nennen ihr Modul ausdrücklich
 * (z. B. `EnsureAppModuleEnabled::class . ':shift_plan'`). Admins dürfen wie im Web auch
 * abgeschaltete Module nutzen.
 */
class EnsureAppModuleEnabled
{
    public function __construct(private readonly ModuleSettingsService $moduleSettingsService)
    {
    }

    public function handle(Request $request, Closure $next, string $moduleSetting): Response
    {
        if ($this->moduleSettingsService->isModuleVisible($moduleSetting)) {
            return $next($request);
        }

        if ($request->user()?->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return $next($request);
        }

        abort(403, 'This module is disabled.');
    }
}
