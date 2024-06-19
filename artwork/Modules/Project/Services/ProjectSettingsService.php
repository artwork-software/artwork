<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;

class ProjectSettingsService
{

    public function store(ProjectCreateSettingRequest $request)
    {
        $settings = app(ProjectCreateSettings::class);
        $settings->attributes = (bool)$request->get('attributes');
        $settings->state = (bool)$request->state;
        $settings->managers = (bool)$request->managers;
        $settings->cost_center = (bool)$request->cost_center;
        $settings->budget_deadline = (bool)$request->budget_deadline;

        $settings->save();
    }
}
