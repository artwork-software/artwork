<?php

namespace Artwork\Modules\AiAssistant\Http\Middleware;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die KI handelt mit den Rechten der Person – dazu gehören die Modul-Schalter des Hauses
 * (ModuleSettingsMiddleware der Oberfläche, Admins wie dort ausgenommen) und „Arbeitszeitberechnung“.
 *
 * Davor stehen die Schalter des Assistenten selbst (AiAssistantAccess): Betreiber- und Haus-Schalter für
 * alle KI-Routen, Stufe B für alles außer der Chat-Historie, Stufe C für Ausführungen (dry_run=false) und
 * die bestätigte Aktion. Die Token-Ausgabe (AiSessionService) prüft dasselbe, aber ein schon ausgegebenes
 * Token oder ein Schlüssel mit ai:*-Scope käme sonst nach dem Abschalten weiter durch.
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

    /**
     * Nur lesend zum Dienstplan: Personensuche der Schichtplanung („Schichten planen“) und die
     * Abwesenheitsliste („Schichtplan sehen“). Das Anlegen einer Abwesenheit (POST /ai/absences) geht in
     * der Oberfläche auch über die eigene Verfügbarkeit und bleibt deshalb ohne Modulbindung.
     */
    private const SHIFT_PLAN_READ_PATTERNS = [
        '#^/api/v1/ai/people$#',
        '#^/api/v1/ai/absences$#',
    ];

    /** Chat-Historie: läuft auch ohne Stufe B. */
    private const CONVERSATION_PATTERN = '#^/api/v1/ai/conversations(/|$)#';

    /** Die bestätigte Aktion gibt es nur mit Stufe C. */
    private const ACTION_PATTERN = '#^/api/v1/ai/actions(/|$)#';

    /** Stunden-Auswertungen nur mit eingeschalteter Arbeitszeitberechnung. */
    private const WORK_TIME_PATTERNS = [
        '#^/api/v1/ai/work-time(/|$)#',
        '#^/api/v1/ai/people/[^/]+/\d+/work-time$#',
    ];

    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
        private readonly AiAssistantAccess $access,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $path = rtrim($request->getPathInfo(), '/');
        $user = $request->user();

        $this->assertAssistantSwitches($request, $path);

        foreach (self::WORK_TIME_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                abort_unless(WorkTimeAccounting::isEnabled(), Response::HTTP_FORBIDDEN, 'Die Arbeitszeitberechnung ist abgeschaltet.');
            }
        }

        $module = self::moduleFor($path, $request->getMethod());
        if ($module !== null && !$this->moduleSettingsService->isModuleVisible($module)) {
            abort_unless(
                $user !== null && $user->hasRole(RoleEnum::ARTWORK_ADMIN->value),
                Response::HTTP_FORBIDDEN,
                'Dieses Modul ist abgeschaltet.'
            );
        }

        return $next($request);
    }

    /**
     * Betreiber-Schalter (`ai.enabled`), Haus-Schalter und Stufen B/C je Anfrage.
     */
    private function assertAssistantSwitches(Request $request, string $path): void
    {
        $user = $request->user();

        abort_unless($this->access->allowed($user), Response::HTTP_FORBIDDEN, 'Der KI-Assistent ist abgeschaltet.');

        if (preg_match(self::CONVERSATION_PATTERN, $path) === 1) {
            return;
        }

        abort_unless(
            $this->access->levelBAllowed($user),
            Response::HTTP_FORBIDDEN,
            'Die Datenauswertung des KI-Assistenten ist abgeschaltet.'
        );

        if (preg_match(self::ACTION_PATTERN, $path) === 1 || $this->isExecution($request)) {
            abort_unless(
                $this->access->levelCAllowed($user),
                Response::HTTP_FORBIDDEN,
                'Aktionen des KI-Assistenten sind abgeschaltet.'
            );
        }
    }

    /**
     * Ausführung heißt: Schreib-Endpunkt (erkennbar an EnsureAiWriteScope) mit `dry_run=false`. Die Vorschau
     * schreibt nichts und bleibt wie bisher mit Stufe B möglich.
     */
    private function isExecution(Request $request): bool
    {
        if ($request->boolean('dry_run', true)) {
            return false;
        }

        return in_array(EnsureAiWriteScope::class, $request->route()?->gatherMiddleware() ?? [], true);
    }

    public static function moduleFor(string $path, string $method = 'GET'): ?string
    {
        foreach (self::SHIFT_PLAN_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return 'shift_plan';
            }
        }

        if (in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            foreach (self::SHIFT_PLAN_READ_PATTERNS as $pattern) {
                if (preg_match($pattern, $path) === 1) {
                    return 'shift_plan';
                }
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
