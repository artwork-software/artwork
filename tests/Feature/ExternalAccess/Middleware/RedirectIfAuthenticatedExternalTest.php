<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\RedirectIfAuthenticatedExternal;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RedirectIfAuthenticatedExternalTest extends TestCase
{
    #[Test]
    public function authenticated_external_is_redirected_from_login_form(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $middleware = app(RedirectIfAuthenticatedExternal::class);
        $request = Request::create('/external/login', 'GET');

        $response = $middleware->handle($request, fn () => response('login form'));

        $this->assertSame(302, $response->getStatusCode());
    }
}
