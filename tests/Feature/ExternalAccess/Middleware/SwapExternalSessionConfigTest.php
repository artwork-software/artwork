<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\SwapExternalSessionConfig;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SwapExternalSessionConfigTest extends TestCase
{
    #[Test]
    public function external_session_cookie_is_used_for_external_routes(): void
    {
        $middleware = app(SwapExternalSessionConfig::class);
        $request = Request::create('/external/dashboard', 'GET');

        $middleware->handle($request, function (): \Symfony\Component\HttpFoundation\Response {
            $this->assertSame(
                config('external_access.session.cookie'),
                config('session.cookie')
            );
            return response('ok');
        });
    }

    #[Test]
    public function web_session_cookie_remains_unchanged_for_web_routes(): void
    {
        $original = config('session.cookie');

        // Simulate a web-only request — middleware never runs, so cookie config is unchanged.
        $this->assertSame($original, config('session.cookie'));
    }
}
