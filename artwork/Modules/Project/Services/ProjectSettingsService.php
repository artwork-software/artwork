<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingsUpdateRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;

class ProjectSettingsService
{

    public function store(
        ProjectCreateSettingsUpdateRequest $request,
        ProjectCreateSettings $projectCreateSettings
    ): void {
        $projectCreateSettings->attributes = $request->boolean('attributes');
        $projectCreateSettings->state = $request->boolean('state');
        $projectCreateSettings->managers = $request->boolean('managers');
        $projectCreateSettings->cost_center = $request->boolean('cost_center');
        $projectCreateSettings->budget_deadline = $request->boolean('budget_deadline');
        $projectCreateSettings->show_artists = $request->boolean('show_artists');
        $projectCreateSettings->save();
    }
}
