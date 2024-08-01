<?php

namespace Tests\Unit\Artwork\Modules\MoneySource\Http\Middleware;

use Artwork\Modules\MoneySource\Http\Middleware\CanEditMoneySource;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CanEditMoneySourceTest extends TestCase
{
    protected CanEditMoneySource $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CanEditMoneySource();
    }

    public function testArtworkAdminCanEdit(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            $route = $this->createMock(Route::class);
            $route->method('parameter')->willReturn('project');
            return $route;
        });

        $user = $this->createMock(User::class);
        $user->method('hasRole')->willReturn(true);

        Auth::shouldReceive('user')->andReturn($user);

        //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        $response = $this->middleware->handle($request, fn($request) => response('Passed'));

        $this->assertEquals('Passed', $response->getContent());
    }

    public function testGlobalProjectBudgetAdminCanEdit(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            $route = $this->createMock(Route::class);
            $route->method('parameter')->willReturn('project');
            return $route;
        });

        $user = $this->createMock(User::class);
        $user->method('hasPermissionTo')->willReturn(true);

        Auth::shouldReceive('user')->andReturn($user);

        //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        $response = $this->middleware->handle($request, fn($request) => response('Passed'));

        $this->assertEquals('Passed', $response->getContent());
    }
}
