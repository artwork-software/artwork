<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;

class ProjectSettingsService
{

    public function store(ProjectCreateSettingRequest $request): void
    {
        $settings = app(ProjectCreateSettings::class);
        $settings->attributes = $request->boolean('attributes');
        $settings->state = $request->boolean('state');
        $settings->managers = $request->boolean('managers');
        $settings->cost_center = $request->boolean('cost_center');
        $settings->budget_deadline = $request->boolean('budget_deadline');
        $settings->save();
    }
}
