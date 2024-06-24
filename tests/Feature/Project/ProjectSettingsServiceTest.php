<?php

namespace Tests\Feature\Project;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Services\ProjectSettingsService;
use Tests\TestCase;

class ProjectSettingsServiceTest extends TestCase
{
    private ProjectSettingsService $projectSettingsService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->projectSettingsService = $this->app->make(ProjectSettingsService::class);
    }

    public function testStore(): void
    {
        // fake request
        $request = new ProjectCreateSettingRequest();
        $request->setMethod('POST');
        $request->request->add([
            'attributes' => true,
            'state' => true,
            'managers' => true,
            'cost_center' => true,
            'budget_deadline' => true,
        ]);
        $projectSettings = app(ProjectCreateSettings::class);
        $this->projectSettingsService->store($request, $projectSettings);

        $this->assertTrue($projectSettings->attributes);
        $this->assertTrue($projectSettings->state);
        $this->assertTrue($projectSettings->managers);
        $this->assertTrue($projectSettings->cost_center);
        $this->assertTrue($projectSettings->budget_deadline);
    }
}
