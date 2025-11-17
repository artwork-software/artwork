<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabMaterialIssueService;
use Illuminate\Http\JsonResponse;

class ProjectMaterialIssueController extends Controller
{
    public function __construct(
        private readonly ProjectTabMaterialIssueService $projectTabMaterialIssueService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabMaterialIssueService->buildMaterialIssuePayload($project)
        );
    }
}

