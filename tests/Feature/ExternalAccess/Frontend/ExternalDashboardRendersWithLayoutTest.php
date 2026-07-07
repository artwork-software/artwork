<?php

namespace Tests\Feature\ExternalAccess\Frontend;

use Artwork\Modules\ExternalAccess\Http\Middleware\HandleExternalInertiaRequests;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalDashboardRendersWithLayoutTest extends TestCase
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

    #[Test]
    public function dashboard_uses_app_external_blade_template(): void
    {
        $middleware = $this->makeMiddleware();
        $request = Request::create('/external/dashboard', 'GET');

        $this->assertSame('app-external', $middleware->rootView($request));
    }

    #[Test]
    public function dashboard_props_contain_no_internal_user_permissions(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $middleware = $this->makeMiddleware();
        $request = Request::create('/external/dashboard', 'GET');
        $request->setLaravelSession($this->app['session.store']);

        $shared = $middleware->share($request);

        foreach (['permissionsArray', 'rolesArray', 'permissions', 'roles'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $shared, "Internal key '{$forbidden}' leaked into external share");
        }

        $serialized = json_encode($this->materializeShare($shared), JSON_THROW_ON_ERROR);

        foreach (['jsPermissions', 'artwork admin', 'can manage workers'] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $serialized,
                "Sensitive string '{$needle}' found in external share",
            );
        }
    }

    /**
     * Resolves Closures in the share so json_encode can serialise the full tree.
     *
     * @param  array<string, mixed>  $share
     * @return array<string, mixed>
     */
    private function materializeShare(array $share): array
    {
        $resolved = [];
        foreach ($share as $key => $value) {
            $resolved[$key] = $value instanceof \Closure ? $value() : $value;
        }
        return $resolved;
    }
}
