<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabTeamService;
use Illuminate\Http\JsonResponse;

class ProjectTeamController extends Controller
{
    public function __construct(
        private readonly ProjectTabTeamService $projectTabTeamService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabTeamService->buildTeamPayload($project)
        );
    }
}


