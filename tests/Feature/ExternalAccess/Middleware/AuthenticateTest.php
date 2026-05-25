<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\Authenticate;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthenticateTest extends TestCase
{
    #[Test]
    public function unauthenticated_request_redirects_to_login(): void
    {
        $middleware = app(Authenticate::class);
        $request = Request::create('/external/dashboard', 'GET');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
    }

    #[Test]
    public function should_use_external_is_set_for_authenticated_requests(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $middleware = app(Authenticate::class);
        $request = Request::create('/external/dashboard', 'GET');

        $middleware->handle($request, function () use ($external): \Symfony\Component\HttpFoundation\Response {
            $this->assertInstanceOf(ExternalAccess::class, Auth::user());
            $this->assertSame($external->id, Auth::id());
            return response('ok');
        });
    }
}
