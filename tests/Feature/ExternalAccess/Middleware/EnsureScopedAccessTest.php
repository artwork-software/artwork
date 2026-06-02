<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\EnsureScopedAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class EnsureScopedAccessTest extends TestCase
{
    /**
     * Runs the middleware against a faked request. $project / $tab may be model
     * instances (resolved route binding) or scalars (to simulate a 404).
     */
    private function dispatch(
        ?ExternalAccess $external,
        mixed $project,
        mixed $tab,
        string $requiredAccess = 'read',
    ): Response {
        $request = Request::create('/external/projects/1/tabs/1', 'GET');
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);

        $route = new Route(['GET'], 'external/projects/{project}/tabs/{tab}', fn () => null);
        $route->bind($request);
        $route->setParameter('project', $project);
        $route->setParameter('tab', $tab);
        $request->setRouteResolver(fn () => $route);

        $middleware = $this->app->make(EnsureScopedAccess::class);

        return $middleware->handle(
            $request,
            fn (Request $r) => new Response('ok'),
            $requiredAccess,
        );
    }

    /**
     * @return array{external: ExternalAccess, project: Project, tab: ProjectTab}
     */
    private function context(): array
    {
        return [
            'external' => ExternalAccess::factory()->active()->create(),
            'project' => Project::factory()->create(),
            'tab' => ProjectTab::factory()->create(),
        ];
    }

    private function assertAborts(int $status, callable $callback): void
    {
        try {
            $callback();
            $this->fail("Expected HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    #[Test]
    public function route_without_scope_returns_403(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();

        $this->assertAborts(403, fn () => $this->dispatch($external, $project, $tab));
    }

    #[Test]
    public function route_with_expired_scope_returns_403(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        ExternalAccessScope::factory()->expired()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $this->assertAborts(403, fn () => $this->dispatch($external, $project, $tab));
    }

    #[Test]
    public function route_with_read_scope_on_write_endpoint_returns_403(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $this->assertAborts(403, fn () => $this->dispatch($external, $project, $tab, 'write'));
    }

    #[Test]
    public function route_with_write_scope_on_write_endpoint_passes(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        ExternalAccessScope::factory()->write()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $response = $this->dispatch($external, $project, $tab, 'write');

        $this->assertSame('ok', $response->getContent());
    }

    #[Test]
    public function route_with_read_scope_on_read_endpoint_passes(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $response = $this->dispatch($external, $project, $tab);

        $this->assertSame('ok', $response->getContent());
    }

    #[Test]
    public function route_attaches_scope_to_request_attributes(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        $scope = ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $request = Request::create('/external/projects/1/tabs/1', 'GET');
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);
        $route = new Route(['GET'], 'external/projects/{project}/tabs/{tab}', fn () => null);
        $route->bind($request);
        $route->setParameter('project', $project);
        $route->setParameter('tab', $tab);
        $request->setRouteResolver(fn () => $route);

        $captured = null;
        $this->app->make(EnsureScopedAccess::class)->handle(
            $request,
            function (Request $r) use (&$captured) {
                $captured = $r->attributes->get('external_scope');
                return new Response('ok');
            },
            'read',
        );

        $this->assertNotNull($captured);
        $this->assertSame($scope->id, $captured->id);
    }

    #[Test]
    public function non_existent_project_or_tab_returns_404(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        // Scalars instead of resolved models simulate an unbound route parameter.
        $this->assertAborts(404, fn () => $this->dispatch($external, 999, 999));
    }
}
