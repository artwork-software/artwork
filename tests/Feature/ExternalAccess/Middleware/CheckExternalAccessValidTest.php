<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\CheckExternalAccessValid;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CheckExternalAccessValidTest extends TestCase
{
    private function runMiddleware(): mixed
    {
        $middleware = app(CheckExternalAccessValid::class);
        $request = Request::create(route('external.dashboard'), 'GET');
        $request->setLaravelSession($this->app['session.store']);

        return $middleware->handle($request, fn () => response('ok'));
    }

    #[Test]
    public function revoked_external_is_logged_out_on_next_request(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);
        $external->forceFill(['revoked_at' => now()])->save();

        $response = $this->runMiddleware();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function expired_external_is_logged_out_on_next_request(): void
    {
        $external = ExternalAccess::factory()->crmAccessExpired()->create();
        Auth::guard('external')->login($external);

        $response = $this->runMiddleware();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function active_external_passes_through(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $response = $this->runMiddleware();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
        $this->assertTrue(Auth::guard('external')->check());
    }
}
