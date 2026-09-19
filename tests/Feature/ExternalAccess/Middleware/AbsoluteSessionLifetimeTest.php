<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\CheckExternalAccessValid;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalLoginService;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class AbsoluteSessionLifetimeTest extends TestCase
{
    private function runMiddleware(): mixed
    {
        $middleware = app(CheckExternalAccessValid::class);
        $request = Request::create(route('external.dashboard'), 'GET');
        $request->setLaravelSession($this->app['session.store']);

        return $middleware->handle($request, fn () => response('ok'));
    }

    #[Test]
    public function session_older_than_absolute_lifetime_is_logged_out(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->session_absolute_lifetime_minutes = 60;
        $settings->save();

        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);
        $this->app['session.store']->put(ExternalLoginService::SESSION_LOGIN_AT_KEY, now()->subMinutes(61)->timestamp);

        $response = $this->runMiddleware();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function session_within_absolute_lifetime_passes(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);
        $this->app['session.store']->put(ExternalLoginService::SESSION_LOGIN_AT_KEY, now()->subMinutes(5)->timestamp);

        $response = $this->runMiddleware();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(Auth::guard('external')->check());
    }
}
