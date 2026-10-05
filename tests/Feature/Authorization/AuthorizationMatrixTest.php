<?php

namespace Tests\Feature\Authorization;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use PHPUnit\Framework\Attributes\Test;
use ReflectionNamedType;
use Symfony\Component\Finder\Finder;
use Tests\Feature\FeatureTestCase;
use Throwable;

/**
 * Jede Web-Route wird als angemeldeter Nutzer ohne Berechtigung, Rolle oder Zugehörigkeit aufgerufen,
 * mit IDs fremder Datensätze; der Snapshot hält je Route die Antwortklasse fest. Wird eine Route
 * durchlässiger als im Snapshot, schlägt der Test fehl; neue Routen müssen in den Snapshot aufgenommen werden.
 *
 * Bedeutung der Klassen:
 *   unauth/forbidden/confirm  geschützt (401/403/423 bzw. Redirect auf Login)
 *   validation                422 – Validierung lief VOR einer möglichen Autorisierung; manuell prüfen
 *   not_found                 404 – Ziel nicht auflösbar, keine Aussage
 *   allowed / redirect        Nutzer ohne Rechte kommt durch (bewusst öffentlich ODER Lücke)
 *   error                     5xx – Autorisierung hat nicht vorher abgebrochen
 *
 * Snapshot aktualisieren (nach bewusster Änderung, Diff prüfen!):
 *   UPDATE_AUTH_MATRIX=1 php artisan test tests/Feature/Authorization/AuthorizationMatrixTest.php
 * Ausführlichen Report (Status, Exception, Location je Route) schreiben:
 *   AUTH_MATRIX_REPORT=1 … → storage/app/authorization_matrix_report.json
 */
final class AuthorizationMatrixTest extends FeatureTestCase
{
    private const SNAPSHOT = __DIR__ . '/__snapshots__/authorization_matrix.json';
    private const REPORT = 'authorization_matrix_report.json';

    /** Eigene Stacks (Passport/Maschinen-API, Horizon, Magic-Link) – dort greifen andere Tests. */
    private const SKIP_URI_PREFIXES = ['api/', 'horizon', 'external', 'oauth/', 'sanctum/', 'broadcasting/', '_ignition', 'up'];

    private const PROTECTED = ['unauth', 'forbidden', 'confirm'];
    private const OPENNESS = [
        'unauth' => 0, 'forbidden' => 0, 'confirm' => 0,
        'not_found' => 1, 'other' => 1,
        'validation' => 2,
        'error' => 3,
        'redirect' => 4, 'allowed' => 4,
    ];

    /** @var array<string, class-string<Model>> lcfirst(Basename) → Klasse */
    private array $modelMap = [];

    private User $nobody;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        // Mail::fake() (FeatureTestCase) liefert einen MailFake; MailService verlangt den echten Manager.
        $this->app->bind(MailService::class, static fn ($app) => new MailService($app['config'], new MailManager($app)));
        $this->modelMap = $this->discoverModels();
    }

    #[Test]
    public function no_web_route_is_more_permissive_than_the_snapshot(): void
    {
        // Welt zuerst, damit der rechtelose Nutzer nirgends der erste Datensatz ist.
        $this->seedWorld();
        $this->nobody = $this->actingAsUserWith([]);

        $matrix = [];
        $report = [];
        foreach ($this->routesUnderTest() as $key => $route) {
            [$class, $details] = $this->probe($route);
            $matrix[$key] = $class;
            $report[$key] = $details;
        }
        ksort($matrix);
        ksort($report);

        if (getenv('AUTH_MATRIX_REPORT')) {
            file_put_contents(
                storage_path('app/' . self::REPORT),
                json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        if (getenv('UPDATE_AUTH_MATRIX') || !file_exists(self::SNAPSHOT)) {
            file_put_contents(
                self::SNAPSHOT,
                json_encode($matrix, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
            $this->addToAssertionCount(1);
            return;
        }

        $snapshot = json_decode((string) file_get_contents(self::SNAPSHOT), true);
        $escalated = [];
        $unknown = [];
        foreach ($matrix as $key => $class) {
            if (!array_key_exists($key, $snapshot)) {
                $unknown[] = "$key → $class";
                continue;
            }
            $before = $snapshot[$key];
            if (self::OPENNESS[$class] > self::OPENNESS[$before] && self::OPENNESS[$class] >= 2) {
                $escalated[] = sprintf('%s: %s → %s (%s)', $key, $before, $class, $report[$key]['status']);
            }
        }

        $message = '';
        if ($escalated !== []) {
            $message .= "Routen sind durchlässiger als im Snapshot:\n  " . implode("\n  ", $escalated) . "\n";
        }
        if ($unknown !== []) {
            $message .= "Neue Routen ohne Snapshot-Eintrag (bewusst einordnen, dann UPDATE_AUTH_MATRIX=1):\n  "
                . implode("\n  ", $unknown) . "\n";
        }
        $this->assertSame('', $message, $message);
    }

    // ---------------------------------------------------------------- Routen

    /** @return array<string, Route> "METHOD uri" → Route, GET zuerst, DELETE zuletzt */
    private function routesUnderTest(): array
    {
        $order = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 2, 'DELETE' => 3];
        $routes = [];
        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if (!in_array('web', $route->middleware(), true)) {
                continue;
            }
            $uri = $route->uri();
            foreach (self::SKIP_URI_PREFIXES as $prefix) {
                if ($uri === rtrim($prefix, '/') || str_starts_with($uri, $prefix)) {
                    continue 2;
                }
            }
            $method = collect($route->methods())->first(fn (string $m) => $m !== 'HEAD' && $m !== 'OPTIONS');
            if ($method === null) {
                continue;
            }
            $routes["$method $uri"] = $route;
        }
        uksort($routes, static function (string $a, string $b) use ($order): int {
            $ma = $order[strtok($a, ' ')] ?? 9;
            $mb = $order[strtok($b, ' ')] ?? 9;
            return $ma <=> $mb ?: strcmp($a, $b);
        });

        return $routes;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function probe(Route $route): array
    {
        $method = collect($route->methods())->first(fn (string $m) => $m !== 'HEAD' && $m !== 'OPTIONS');
        $url = $this->resolveUrl($route);

        // Nach jedem Request neu anmelden: logout/Passwort-Routen dürfen die Sitzung nicht für den Rest kippen.
        $this->actingAs($this->nobody);

        try {
            $response = $this->call($method, $url, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_X_INERTIA' => 'true',
                'HTTP_X_INERTIA_VERSION' => (string) Inertia::getVersion(),
            ]);
        } catch (Throwable $exception) {
            return ['error', ['status' => 500, 'url' => $url, 'exception' => $this->describe($exception)]];
        }

        return [$this->classify($response), $this->details($response, $url)];
    }

    private function classify(TestResponse $response): string
    {
        $status = $response->getStatusCode();

        return match (true) {
            $status >= 200 && $status < 300 => 'allowed',
            in_array($status, [301, 302, 303], true) => $this->isLoginRedirect($response) ? 'unauth' : 'redirect',
            $status === 401 => 'unauth',
            $status === 403 => 'forbidden',
            $status === 404 => 'not_found',
            $status === 422 => 'validation',
            $status === 423 => 'confirm',
            $status >= 500 => 'error',
            default => 'other',
        };
    }

    private function isLoginRedirect(TestResponse $response): bool
    {
        $location = (string) $response->headers->get('Location');

        return str_contains($location, '/login') || str_contains($location, '/user/confirm-password');
    }

    /** @return array<string, mixed> */
    private function details(TestResponse $response, string $url): array
    {
        $details = ['status' => $response->getStatusCode(), 'url' => $url];
        if ($location = $response->headers->get('Location')) {
            $details['location'] = $location;
        }
        if ($response->exception !== null) {
            $details['exception'] = $this->describe($response->exception);
        }

        return $details;
    }

    private function describe(Throwable $exception): string
    {
        return sprintf(
            '%s: %s (%s:%d)',
            class_basename($exception),
            Str::limit($exception->getMessage(), 160),
            basename($exception->getFile()),
            $exception->getLine()
        );
    }

    // ------------------------------------------------------ Parameter-Auflösung

    private function resolveUrl(Route $route): string
    {
        $typed = [];
        foreach ($route->signatureParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && is_subclass_of($type->getName(), Model::class)) {
                $typed[$parameter->getName()] = $type->getName();
            }
        }

        $uri = $route->uri();
        $segments = explode('/', $uri);
        $values = [];
        foreach ($route->parameterNames() as $name) {
            $index = array_search('{' . $name . '}', $segments, true);
            if ($index === false) {
                $index = array_search('{' . $name . '?}', $segments, true);
            }
            $previous = $index > 0 ? $segments[$index - 1] : '';
            $values[$name] = $this->resolveParameter($name, $typed[$name] ?? null, $previous);
        }

        $path = $uri;
        foreach ($values as $name => $value) {
            $path = str_replace(['{' . $name . '}', '{' . $name . '?}'], (string) $value, $path);
        }

        return '/' . ltrim($path, '/');
    }

    private function resolveParameter(string $name, ?string $modelClass, string $previousSegment): string|int
    {
        if ($modelClass === null) {
            $modelClass = $this->guessModelClass($name, $previousSegment);
        }
        if ($modelClass !== null) {
            return $this->modelId($modelClass);
        }

        return match (true) {
            in_array($name, ['date', 'startDate', 'endDate', 'day', 'startBudgetDeadline', 'endBudgetDeadline'], true) => now()->format('Y-m-d'),
            $name === 'letters' => 'a',
            $name === 'filename' => 'test.txt',
            in_array($name, ['token', 'notificationKey', 'scope', 'guard', 'privacyMode', 'userType', 'state', 'model'], true) => 'x',
            default => 1,
        };
    }

    /** @return class-string<Model>|null */
    private function guessModelClass(string $name, string $previousSegment): ?string
    {
        if ($name === 'projects') {
            return Project::class; // Route::bind('projects') in RouteServiceProvider
        }
        $candidates = [
            lcfirst(Str::studly(preg_replace('/(_id|Id)$/', '', $name))),
            lcfirst(Str::studly(Str::singular(preg_replace('/(_id|Id)$/', '', $name)))),
        ];
        if ($previousSegment !== '' && !str_starts_with($previousSegment, '{')) {
            $candidates[] = lcfirst(Str::studly(Str::singular($previousSegment)));
        }
        foreach ($candidates as $candidate) {
            if (isset($this->modelMap[$candidate])) {
                return $this->modelMap[$candidate];
            }
        }

        return null;
    }

    /** Fremder Datensatz: erster vorhandener, sonst per Factory, sonst nicht existierende ID. */
    private function modelId(string $class): int|string
    {
        try {
            $query = method_exists($class, 'withTrashed') ? $class::withTrashed() : $class::query();
            if ($class === User::class) {
                $query->whereKeyNot($this->nobody->getKey());
            }
            $model = $query->orderBy((new $class())->getKeyName())->first();
            if ($model === null && method_exists($class, 'factory')) {
                $model = $class::factory()->create();
            }
            if ($model !== null) {
                return $model->getRouteKey() ?? $model->getKey();
            }
        } catch (Throwable) {
            // Tabelle/Factory nicht nutzbar → 404 ist die ehrliche Antwort
        }

        return 999999;
    }

    /** @return array<string, class-string<Model>> */
    private function discoverModels(): array
    {
        $map = [];
        $directories = array_filter([base_path('artwork/Modules'), base_path('app/Models')], 'is_dir');
        $finder = (new Finder())->files()->name('*.php')->in($directories)
            ->path('/Models/')->notPath('/Models\/(Traits|Concerns|Enums|Contracts|Scopes|Casts)/');
        foreach ($finder as $file) {
            $relative = str_replace([base_path() . '/', '.php', '/'], ['', '', '\\'], $file->getPathname());
            $class = preg_replace('/^artwork\\\\/', 'Artwork\\', $relative);
            $class = preg_replace('/^app\\\\/', 'App\\', $class);
            if (!class_exists($class) || !is_subclass_of($class, Model::class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }
            $map[lcfirst(class_basename($class))] ??= $class;
        }

        return $map;
    }

    /**
     * Fremde Welt: von jeder Modellklasse ein Datensatz, der dem Testnutzer nicht gehört –
     * per Factory, sonst per Schema-Analyse (Pflichtspalten + Fremdschlüssel minimal befüllt).
     */
    private function seedWorld(): void
    {
        foreach ($this->modelMap as $class) {
            try {
                if ($class::query()->exists()) {
                    continue;
                }
                if (method_exists($class, 'factory')) {
                    $class::factory()->create();
                }
            } catch (Throwable) {
                // Factory ohne Standardwelt → Schema-Fallback unten
            }
            try {
                if (!$class::query()->exists()) {
                    $this->seedRow((new $class())->getTable());
                }
            } catch (Throwable) {
                // Tabelle nicht befüllbar – die Route landet dann auf 404 und wird als not_found geführt
            }
        }
    }

    /** @var array<string, bool> Tabellen, die gerade befüllt werden (Zyklus-Schutz) */
    private array $seeding = [];

    private function seedRow(string $table, int $depth = 0): int|string|null
    {
        if ($depth > 3 || isset($this->seeding[$table]) || !Schema::hasTable($table)) {
            return null;
        }
        $this->seeding[$table] = true;
        try {
            $foreign = [];
            foreach (Schema::getForeignKeys($table) as $key) {
                foreach ($key['columns'] as $column) {
                    $foreign[$column] = $key['foreign_table'];
                }
            }
            $row = [];
            foreach (Schema::getColumns($table) as $column) {
                $name = $column['name'];
                if ($column['auto_increment']) {
                    continue;
                }
                if (isset($foreign[$name])) {
                    $id = DB::table($foreign[$name])->value('id') ?? $this->seedRow($foreign[$name], $depth + 1);
                    if ($id === null && $column['nullable']) {
                        continue;
                    }
                    $row[$name] = $id ?? 1;
                    continue;
                }
                if ($column['nullable'] || $column['default'] !== null) {
                    continue;
                }
                $row[$name] = $this->sampleValue($column);
            }
            $id = DB::table($table)->insertGetId($row);
            return $id ?: (DB::table($table)->value('id') ?? 1);
        } finally {
            unset($this->seeding[$table]);
        }
    }

    /** @param array<string, mixed> $column */
    private function sampleValue(array $column): mixed
    {
        $type = strtolower((string) $column['type_name']);
        $full = strtolower((string) $column['type']);
        if ($type === 'enum' && preg_match("/enum\\('([^']*)'/", $full, $m)) {
            return $m[1];
        }
        if (in_array($type, ['tinyint', 'boolean', 'bool'], true)) {
            return 0;
        }
        if (str_contains($type, 'int') || in_array($type, ['decimal', 'float', 'double', 'numeric'], true)) {
            return 1;
        }
        if ($type === 'json') {
            return '[]';
        }
        if ($type === 'date') {
            return now()->toDateString();
        }
        if (in_array($type, ['datetime', 'timestamp'], true)) {
            return now()->toDateTimeString();
        }
        if ($type === 'time') {
            return '10:00:00';
        }
        $length = preg_match('/\\((\\d+)\\)/', $full, $m) ? (int) $m[1] : 32;
        return substr('m-' . Str::lower(Str::random(30)), 0, max(1, min($length, 32)));
    }
}
