<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalTabComponentResolver;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExternalProjectTabController extends Controller
{
    public function __construct(
        private readonly ExternalTabComponentResolver $componentResolver,
    ) {
    }

    public function show(Request $request, Project $project, ProjectTab $tab): Response
    {
        /** @var ExternalAccessScope $scope */
        $scope = $request->attributes->get('external_scope');

        $components = $this->componentResolver->resolveTabComponents($project, $tab);

        return Inertia::render('Project/TabShow', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'tab' => [
                'id' => $tab->id,
                'name' => $tab->name,
            ],
            'scope' => [
                'access_type' => $scope->access_type->value,
                'valid_to' => $scope->valid_to->toIso8601String(),
            ],
            'components' => $components,
        ]);
    }
}
