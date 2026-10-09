<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Services\AppProjectService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppProjectController extends Controller
{
    public function __construct(
        private readonly AppProjectService $projectService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'projects' => $this->projectService->getProjects($request->user()),
        ]);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'project' => $this->projectService->getProject($request->user(), $project),
        ]);
    }

    public function showTab(Request $request, Project $project, ProjectTab $projectTab): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless(
            $projectTab->visibleForUser($request->user()),
            403,
            'You are not allowed to view this tab.',
        );

        return response()->json(
            $this->projectService->getTabComponents($request->user(), $project, $projectTab),
        );
    }
}
