<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Http\Middleware\HandleExternalInertiaRequests;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HandleExternalInertiaRequestsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeMiddleware(): HandleExternalInertiaRequests
    {
        $settings = Mockery::mock(GeneralSettings::class);
        $settings->page_title = 'Test App';
        $settings->business_name = 'Test Business';
        $settings->small_logo_path = '';
        $settings->big_logo_path = '';
        $settings->banner_path = '';
        $settings->impressum_link = '';
        $settings->privacy_link = '';

        $this->app->instance(GeneralSettings::class, $settings);

        return $this->app->make(HandleExternalInertiaRequests::class);
    }

    private function shareFor(?ExternalAccess $external): array
    {
        if ($external !== null) {
            Auth::guard('external')->login($external);
        }

        $middleware = $this->makeMiddleware();
        $request = Request::create('/external/dashboard', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn ($guard = null) => Auth::guard($guard ?: 'external')->user());

        return $middleware->share($request);
    }

    #[Test]
    public function share_does_not_contain_permissions_or_roles(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $shared = $this->shareFor($external);

        $authShape = $shared['auth']['external'] ?? [];

        $this->assertArrayNotHasKey('permissions', $authShape);
        $this->assertArrayNotHasKey('permissionsArray', $authShape);
        $this->assertArrayNotHasKey('roles', $authShape);
        $this->assertArrayNotHasKey('rolesArray', $authShape);
        $this->assertArrayNotHasKey('all_permissions', $authShape);

        // Top-level share must not leak internal-user-only keys either.
        $this->assertArrayNotHasKey('permissions', $shared);
        $this->assertArrayNotHasKey('roles', $shared);
    }

    #[Test]
    public function share_contains_external_identity_and_no_password(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $shared = $this->shareFor($external);

        $this->assertArrayHasKey('auth', $shared);
        $this->assertArrayHasKey('external', $shared['auth']);
        $this->assertSame($external->id, $shared['auth']['external']['id']);
        $this->assertArrayNotHasKey('password', $shared['auth']['external']);
        $this->assertArrayNotHasKey('remember_token', $shared['auth']['external']);
    }

    #[Test]
    public function share_returns_null_external_for_unauthenticated_request(): void
    {
        $shared = $this->shareFor(null);

        $this->assertNull($shared['auth']['external']);
    }
}
