<?php

namespace Tests\Feature\Routing;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Jede registrierte Route muss auf eine existierende Controller-Methode zeigen. Sonst liefert der
 * Aufruf erst zur Laufzeit einen 500 (BadMethodCallException) statt eines 404/405.
 */
final class RouteActionsExistTest extends TestCase
{
    #[Test]
    public function every_controller_route_points_to_an_existing_method(): void
    {
        $missing = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => is_string($route->getAction('uses')))
            ->map(function (Route $route): ?string {
                [$controller, $method] = array_pad(explode('@', $route->getAction('uses'), 2), 2, '__invoke');
                if (!class_exists($controller)) {
                    return $route->uri() . ' → ' . $controller . ' (Klasse fehlt)';
                }

                return method_exists($controller, $method)
                    ? null
                    : $route->uri() . ' → ' . $controller . '::' . $method;
            })
            ->filter()
            ->values()
            ->all();

        $this->assertSame([], $missing);
    }
}
