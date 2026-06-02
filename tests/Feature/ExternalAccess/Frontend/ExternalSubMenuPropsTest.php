<?php

namespace Tests\Feature\ExternalAccess\Frontend;

use Artwork\Modules\ExternalAccess\Http\Middleware\HandleExternalInertiaRequests;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalSubMenuPropsTest extends TestCase
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
    public function accessible_scopes_are_shared_with_project_and_tab_in_share(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        $project = Project::factory()->create(['name' => 'Project A']);
        $tabOne = ProjectTab::factory()->create(['name' => 'Tab One']);
        $tabTwo = ProjectTab::factory()->create(['name' => 'Tab Two']);

        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tabOne->id,
        ]);
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tabTwo->id,
        ]);

        Auth::guard('external')->login($external);

        $middleware = $this->makeMiddleware();
        $request = Request::create('/external/dashboard', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);

        $shared = $middleware->share($request);
        $scopes = $shared['accessible_scopes'] instanceof \Closure ? ($shared['accessible_scopes'])() : $shared['accessible_scopes'];

        $this->assertCount(2, $scopes);
        foreach ($scopes as $scope) {
            $this->assertSame($project->id, $scope['project']['id']);
            $this->assertSame('Project A', $scope['project']['name']);
            $this->assertContains($scope['tab']['name'], ['Tab One', 'Tab Two']);
            $this->assertArrayHasKey('access_type', $scope);
            $this->assertArrayHasKey('valid_to', $scope);
        }
    }

    #[Test]
    public function expired_scopes_are_not_shared(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        ExternalAccessScope::factory()->expired()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        Auth::guard('external')->login($external);

        $middleware = $this->makeMiddleware();
        $request = Request::create('/external/dashboard', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);

        $shared = $middleware->share($request);
        $scopes = $shared['accessible_scopes'] instanceof \Closure ? ($shared['accessible_scopes'])() : $shared['accessible_scopes'];

        $this->assertSame([], $scopes);
    }
}
