<?php

namespace Artwork\Modules\AiAssistant\Http\Middleware;

use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die KI handelt mit den Rechten der Person – dazu gehören die Modul-Schalter des Hauses
 * (ModuleSettingsMiddleware der Oberfläche, Admins wie dort ausgenommen) und „Arbeitszeitberechnung“.
 * Die Stufen B/C des Assistenten prüft die Token-Ausgabe (AiSessionService, Token-TTL 15 min).
 */
class EnsureAiHouseSwitches
{
    /**
     * Pfad-Präfixe der KI-Routen → Modul (längster Präfix gewinnt; ein Präfix trifft auch Unterpfade).
     */
    private const MODULE_BY_PREFIX = [
        '/api/v1/ai/shift-plan' => 'shift_plan',
        '/api/v1/ai/shifts' => 'shift_plan',
        '/api/v1/ai/shift-assignments' => 'shift_plan',
        '/api/v1/ai/shift-rule-violations' => 'shift_plan',
        '/api/v1/ai/crafts' => 'shift_plan',
        '/api/v1/ai/inventory' => 'inventory',
        '/api/v1/ai/contracts' => 'contracts',
        '/api/v1/ai/tasks' => 'tasks',
        '/api/v1/ai/me/tasks' => 'tasks',
        '/api/v1/ai/checklists' => 'tasks',
    ];

    /**
     * Termin-Schichten liegen unter /ai/events, die Auslastung (Schicht-Planzahlen) unter /ai/people; beides
     * gehört zum Dienstplan.
     */
    private const SHIFT_PLAN_PATTERNS = [
        '#^/api/v1/ai/events/\d+/shifts$#',
        '#^/api/v1/ai/people/[^/]+/\d+/workload$#',
    ];

    /** Stunden-Auswertungen nur mit eingeschalteter Arbeitszeitberechnung. */
    private const WORK_TIME_PATTERNS = [
        '#^/api/v1/ai/work-time(/|$)#',
        '#^/api/v1/ai/people/[^/]+/\d+/work-time$#',
    ];

    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $path = rtrim($request->getPathInfo(), '/');
        $user = $request->user();

        foreach (self::WORK_TIME_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                abort_unless(WorkTimeAccounting::isEnabled(), Response::HTTP_FORBIDDEN, 'Die Arbeitszeitberechnung ist abgeschaltet.');
            }
        }

        $module = self::moduleFor($path);
        if ($module !== null && !$this->moduleSettingsService->isModuleVisible($module)) {
            abort_unless(
                $user !== null && $user->hasRole(RoleEnum::ARTWORK_ADMIN->value),
                Response::HTTP_FORBIDDEN,
                'Dieses Modul ist abgeschaltet.'
            );
        }

        return $next($request);
    }

    public static function moduleFor(string $path): ?string
    {
        foreach (self::SHIFT_PLAN_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return 'shift_plan';
            }
        }

        $best = null;
        foreach (array_keys(self::MODULE_BY_PREFIX) as $prefix) {
            if (($path === $prefix || str_starts_with($path, $prefix . '/'))
                && ($best === null || strlen($prefix) > strlen($best))) {
                $best = $prefix;
            }
        }

        return $best === null ? null : self::MODULE_BY_PREFIX[$best];
    }
}
