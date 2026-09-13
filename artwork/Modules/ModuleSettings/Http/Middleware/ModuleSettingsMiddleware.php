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
    /**
     * Pfad-Präfixe → Modul. Ein Präfix trifft den Pfad selbst und alle Unterpfade
     * (/projects, /projects/5/...), damit die Modul-Abschaltung nicht nur die Einstiegsseite,
     * sondern auch Detail- und API-Routen des Moduls sperrt. Die Reihenfolge ist egal, der
     * längste passende Präfix gewinnt (z. B. /inventory-management vor /inventory).
     */
    private const ROUTE_SETTING_MAPPING = [
        '/projects' => 'projects',
        '/calendar/view' => 'room_assignment',
        '/shifts/view' => 'shift_plan',
        '/shift-plan' => 'shift_plan',
        '/inventory' => 'inventory',
        '/inventory-management' => 'inventory',
        '/issue-of-material' => 'inventory',
        '/extern-issue-of-material' => 'inventory',
        '/material-sets' => 'inventory',
        '/tasks/own' => 'tasks',
        '/money_sources' => 'sources_of_funding',
        '/users' => 'users',
        '/contracts' => 'contracts',
        '/document-requests' => 'contracts',
        '/planning-event-calendar' => 'planning_calendar',
        '/bi/dashboard' => 'business_intelligence',
        '/bi/export' => 'business_intelligence',
        '/crm' => 'crm',
    ];

    /**
     * Präfixe, die NUR exakt treffen: unter /users liegen auch eigenes Profil und Einsatzplan,
     * die ohne Personal-Modul weiter erreichbar bleiben müssen. Unter /projects liegen Projekt-
     * Endpunkte, die Kalender, Dienstplan, Budget und Inventar querschnittlich nutzen (Suche,
     * Tabs, Termine) – der Modul-Schalter sperrt dort wie bisher nur die Einstiegsseite.
     */
    private const EXACT_MATCH_ONLY = [
        '/users',
        '/projects',
    ];

    /**
     * Endpunkte unterhalb eines Modul-Präfixes, die andere Module brauchen und deshalb auch bei
     * abgeschaltetem Modul erreichbar bleiben (sonst 401 im Budget, Projekt-Tab oder Projektteam).
     */
    private const CROSS_MODULE_PATTERNS = [
        '#^/money_sources/search(/|$)#',            // Finanzierungsquellen-Suche in Budget-Zellen
        '#^/contracts/\d+(/|$)#',                   // Vertrag anzeigen/laden/ändern aus dem Projekt-Tab
        '#^/crm/contacts-search$#',                 // Kontakt-Suche im Projektteam / Dokumentenanfragen
        '#^/crm/contacts/\d+/(data|tooltip)$#',     // Kontakt-Popover im Projektteam
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

        // getPathInfo() statt getRequestUri(): ohne Query-String, sonst umgeht "?x=1" die Prüfung.
        $setting = self::resolveSetting($request->getPathInfo());

        if ($setting === null) {
            return $next($request);
        }

        if ($this->moduleSettingsService->isModuleVisible($setting)) {
            return $next($request);
        }

        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value) && !in_array($setting, self::NO_ADMIN_BYPASS)) {
            return $next($request);
        }

        throw new UnauthorizedException(401);
    }

    public static function resolveSetting(string $path): ?string
    {
        $path = rtrim($path, '/') ?: '/';

        foreach (self::CROSS_MODULE_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return null;
            }
        }

        $bestPrefix = null;

        foreach (array_keys(self::ROUTE_SETTING_MAPPING) as $prefix) {
            $matches = $path === $prefix
                || (!in_array($prefix, self::EXACT_MATCH_ONLY, true) && str_starts_with($path, $prefix . '/'));
            if ($matches) {
                if ($bestPrefix === null || strlen($prefix) > strlen($bestPrefix)) {
                    $bestPrefix = $prefix;
                }
            }
        }

        return $bestPrefix === null ? null : self::ROUTE_SETTING_MAPPING[$bestPrefix];
    }
}
