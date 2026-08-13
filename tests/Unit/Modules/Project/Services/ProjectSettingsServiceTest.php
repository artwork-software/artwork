<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingsUpdateRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Services\ProjectSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectSettingsServiceTest extends TestCase
{
    private ProjectSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectSettingsService();
    }

    #[Test]
    public function store_writes_request_booleans_to_settings(): void
    {
        $settings = app(ProjectCreateSettings::class);

        $request = ProjectCreateSettingsUpdateRequest::create('/', 'POST', [
            'attributes' => '1',
            'state' => '0',
            'state_required' => '1',
            'managers' => '1',
            'cost_center' => '0',
            'budget_deadline' => '1',
            'show_artists' => '0',
        ]);

        $this->service->store($request, $settings);

        $fresh = app(ProjectCreateSettings::class);
        $this->assertTrue($fresh->attributes);
        $this->assertFalse($fresh->state);
        $this->assertTrue($fresh->state_required);
        $this->assertTrue($fresh->managers);
        $this->assertFalse($fresh->cost_center);
        $this->assertTrue($fresh->budget_deadline);
        $this->assertFalse($fresh->show_artists);
    }
}
