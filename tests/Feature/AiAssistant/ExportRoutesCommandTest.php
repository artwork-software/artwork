<?php

namespace Tests\Feature\AiAssistant;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** core-routes-export: routes-web.json fuer artwork-ai (Konzept C5 §2). */
final class ExportRoutesCommandTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = storage_path('framework/testing/routes-web-test.json');
        @unlink($this->path);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    /** @return array<int, array<string, mixed>> */
    private function export(): array
    {
        $this->artisan('ai:export-routes', ['--path' => $this->path])->assertSuccessful();

        return json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<int, array<string, mixed>> $routes */
    private function names(array $routes): array
    {
        return array_column($routes, 'name');
    }

    #[Test]
    public function it_exports_named_parameterless_get_web_routes(): void
    {
        $routes = $this->export();

        $this->assertNotEmpty($routes);
        $this->assertContains('dashboard', $this->names($routes));
        $this->assertContains('projects', $this->names($routes));
    }

    #[Test]
    public function it_skips_routes_with_parameters(): void
    {
        foreach ($this->export() as $route) {
            $this->assertStringNotContainsString('{', $route['path'], $route['name'] . ' traegt einen Parameter');
        }
    }

    #[Test]
    public function it_skips_downloads_exports_and_pdfs(): void
    {
        foreach ($this->names($this->export()) as $name) {
            foreach (['.download', '.export', '.pdf'] as $needle) {
                $this->assertStringNotContainsString($needle, $name);
            }
        }
    }

    #[Test]
    public function it_skips_api_paths_which_are_not_pages(): void
    {
        foreach ($this->export() as $route) {
            $this->assertStringStartsNotWith('/api/', $route['path'], $route['name'] . ' ist kein Seitenpfad');
        }
    }

    #[Test]
    public function a_route_added_at_runtime_shows_up_and_a_parameterised_one_does_not(): void
    {
        Route::middleware('web')->get('/test-only/exportable-page', fn (): string => 'x')
            ->name('test-only.exportable-page');
        Route::middleware('web')->get('/test-only/with/{id}', fn (): string => 'x')
            ->name('test-only.with-parameter');
        Route::middleware('web')->get('/test-only/report', fn (): string => 'x')
            ->name('test-only.report.download');

        $names = $this->names($this->export());

        $this->assertContains('test-only.exportable-page', $names);
        $this->assertNotContains('test-only.with-parameter', $names);
        $this->assertNotContains('test-only.report.download', $names);
    }

    #[Test]
    public function the_output_is_byte_identical_across_two_runs(): void
    {
        $this->artisan('ai:export-routes', ['--path' => $this->path])->assertSuccessful();
        $first = file_get_contents($this->path);

        $this->artisan('ai:export-routes', ['--path' => $this->path])->assertSuccessful();
        $second = file_get_contents($this->path);

        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_is_sorted_by_route_name(): void
    {
        $names = $this->names($this->export());
        $sorted = $names;
        sort($sorted);

        $this->assertSame($sorted, $names);
    }

    /** Konzept C5 §2: die Hauptnavigation muss Labels tragen, sonst kann artwork-ai nicht verlinken. */
    #[Test]
    public function the_main_navigation_carries_german_labels(): void
    {
        $byName = collect($this->export())->keyBy('name');

        $expected = [
            'projects' => 'Projekte',
            'events' => 'Kalender',
            'shifts.plan' => 'Dienstpläne',
            'inventory.index' => 'Inventar',
            'tool.branding' => 'Tool-Einstellungen',
        ];

        foreach ($expected as $name => $label) {
            $this->assertArrayHasKey($name, $byName, "Route {$name} fehlt im Export");
            $this->assertSame($label, $byName[$name]['label']);
            $this->assertNotNull($byName[$name]['area']);
        }
    }

    #[Test]
    public function an_unknown_route_is_exported_without_a_label(): void
    {
        Route::middleware('web')->get('/test-only/no-label', fn (): string => 'x')
            ->name('test-only.no-label');

        $entry = collect($this->export())->firstWhere('name', 'test-only.no-label');

        $this->assertNotNull($entry);
        $this->assertNull($entry['label']);
        $this->assertNull($entry['area']);
    }

    #[Test]
    public function another_locale_is_rejected_for_now(): void
    {
        $this->artisan('ai:export-routes', ['--path' => $this->path, '--locale' => 'en'])
            ->assertFailed();
    }
}
