<?php

namespace Artwork\Modules\AiAssistant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

/**
 * Exportiert die verlinkbaren Seiten von artwork als routes-web.json (core-routes-export;
 * Konzept C5 §2).
 *
 * artwork-ai braucht die Zuordnung Route-Name -> Pfad -> Menuebezeichnung, um in Antworten auf die
 * richtige Seite zu verlinken. Ohne dieses Artefakt muesste es eine vollstaendige PHP-Umgebung
 * booten, nur um Routen zu lesen.
 *
 * Das Ergebnis haengt als Asset am Release-Tag; artwork-ai friert es pro Version ein.
 */
class ExportRoutesCommand extends Command
{
    protected $signature = 'ai:export-routes {--path= : Zieldatei, Standard routes-web.json im Projekt-Root}'
        . ' {--locale=de : Sprache der Labels; derzeit ist nur de umgesetzt}';

    protected $description = 'Exportiert benannte Web-Seiten als routes-web.json fuer artwork-ai.';

    /**
     * Routen, die technisch GET und ohne Parameter sind, aber keine Seite liefern — Downloads,
     * Exporte, erzeugte Dateien. Ein Link dorthin waere fuer eine Antwort nutzlos.
     */
    private const EXCLUDED_NAME_PATTERNS = [
        '*.download',
        '*.export',
        '*.pdf',
        '*.export.*',
        '*.download.*',
        // JSON-Endpunkte der KI-Bar, keine Seiten
        'ai.conversations.*',
    ];

    /**
     * Pfad-Praefixe, die keine Seite liefern. In web.php liegen einige JSON-Helfer unter api/ —
     * sie tragen die web-Middleware, sind aber nichts, worauf eine Antwort verlinken duerfte.
     */
    private const EXCLUDED_PATH_PREFIXES = [
        'api/',
    ];

    /**
     * Route-Name => Navigations-Label und Bereich, abgeleitet aus resources/js/Layouts/SubMenu.vue.
     * Einmalige Pflege: Kommt ein Menuepunkt dazu, gehoert er hier ergaenzt. Routen ohne Eintrag
     * werden trotzdem exportiert, dann mit label = null.
     *
     * @var array<string, array{label: string, area: string}>
     */
    private const NAVIGATION_LABELS = [
        'areas.management' => ['label' => 'Rooms', 'area' => 'System'],
        'bi.dashboard' => ['label' => 'BI Dashboard', 'area' => 'BI Dashboard'],
        'calendar.settings' => ['label' => 'Calendar', 'area' => 'System'],
        'checklist_templates.management' => ['label' => 'Checklists', 'area' => 'System'],
        'compensation-day-offs.dashboard' => ['label' => 'Compensation days overview', 'area' => 'Shift plan'],
        'contracts.index' => ['label' => 'Contracts', 'area' => 'Documents'],
        'crm.index' => ['label' => 'CRM', 'area' => 'CRM'],
        'crm.settings.index' => ['label' => 'CRM Settings', 'area' => 'System'],
        'dashboard' => ['label' => 'Dashboard', 'area' => 'Dashboard'],
        'document-requests.index' => ['label' => 'Document requests', 'area' => 'Documents'],
        'event-verifications.index' => ['label' => 'Event Verifications', 'area' => 'Calendar'],
        'event_types.management' => ['label' => 'Events', 'area' => 'System'],
        'events' => ['label' => 'Calendar', 'area' => 'Calendar'],
        'inventory-management.article.planning' => ['label' => 'Article Planning', 'area' => 'Inventory System'],
        'inventory.index' => ['label' => 'Inventory', 'area' => 'Inventory System'],
        'issue-of-material.index' => ['label' => 'Material Issues', 'area' => 'Inventory System'],
        'money_sources.index' => ['label' => 'Sources of funding', 'area' => 'Sources of funding'],
        'money_sources.settings' => ['label' => 'Sources of funding', 'area' => 'System'],
        'planning-event-calendar.index' => ['label' => 'Planning Calendar', 'area' => 'Calendar'],
        'project.settings' => ['label' => 'Projects', 'area' => 'System'],
        'projects' => ['label' => 'Projects', 'area' => 'Projects'],
        'projects.trashed' => ['label' => 'Recycle bin', 'area' => 'Recycle bin'],
        'shift.settings' => ['label' => 'Shift settings', 'area' => 'System'],
        'shifts.approvals.changes' => ['label' => 'Shift plan Change list', 'area' => 'Shift plan'],
        'shifts.approvals.requests' => ['label' => 'Requested duty rosters', 'area' => 'Shift plan'],
        'shifts.approvals.review' => ['label' => 'Shift plan Review requests', 'area' => 'Shift plan'],
        'shifts.plan' => ['label' => 'Duty rosters', 'area' => 'Shift plan'],
        'shifts.plan.list-view' => ['label' => 'Shift plan list view', 'area' => 'Shift plan'],
        'shifts.presets' => ['label' => 'Shift templates', 'area' => 'Shift plan'],
        'shifts.week-status' => ['label' => 'Week status', 'area' => 'Shift plan'],
        'tasks.own' => ['label' => 'To-dos', 'area' => 'To-dos'],
        'tool.branding' => ['label' => 'Tool Settings', 'area' => 'System'],
        'user.operationPlan' => ['label' => 'My Operational plan', 'area' => 'Shift plan'],
        'users' => ['label' => 'Users', 'area' => 'Users'],
        'work-time-request.index' => ['label' => 'Work time change requests', 'area' => 'Shift plan'],
    ];

    public function handle(): int
    {
        $locale = (string) $this->option('locale');

        if ($locale !== 'de') {
            $this->error('Derzeit ist nur --locale=de umgesetzt.');

            return self::FAILURE;
        }

        $path = $this->option('path') ?: base_path('routes-web.json');

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => $this->isLinkablePage($route))
            ->map(fn (RoutingRoute $route): array => $this->describe($route, $locale))
            ->sortBy('name')
            ->values()
            ->all();

        // Deterministisch: sortiert, feste Flags, abschliessender Zeilenumbruch. Sonst erzeugt jeder
        // Lauf einen Diff und das Artefakt waere im Release nicht vergleichbar.
        file_put_contents(
            $path,
            json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );

        $withoutLabel = collect($routes)->whereNull('label')->count();

        $this->info(sprintf(
            '%d Route(n) nach %s exportiert, davon %d ohne Label.',
            count($routes),
            $path,
            $withoutLabel
        ));

        return self::SUCCESS;
    }

    private function isLinkablePage(RoutingRoute $route): bool
    {
        $name = $route->getName();

        if ($name === null || !in_array('GET', $route->methods(), true)) {
            return false;
        }

        if (!in_array('web', $route->gatherMiddleware(), true)) {
            return false;
        }

        // Parameter-Routen sind ohne konkreten Datensatz nicht verlinkbar.
        if (str_contains($route->uri(), '{')) {
            return false;
        }

        foreach (self::EXCLUDED_NAME_PATTERNS as $pattern) {
            if (fnmatch($pattern, $name)) {
                return false;
            }
        }

        foreach (self::EXCLUDED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($route->uri(), $prefix)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{name: string, path: string, label: string|null, area: string|null} */
    private function describe(RoutingRoute $route, string $locale): array
    {
        $name = (string) $route->getName();
        $entry = self::NAVIGATION_LABELS[$name] ?? null;

        return [
            'name' => $name,
            'path' => '/' . ltrim($route->uri(), '/'),
            'label' => $entry !== null ? Lang::get($entry['label'], [], $locale) : null,
            'area' => $entry !== null ? Lang::get($entry['area'], [], $locale) : null,
        ];
    }
}
