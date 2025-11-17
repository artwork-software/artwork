<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabStatusService;
use Illuminate\Http\JsonResponse;

class ProjectStatusController extends Controller
{
    public function __construct(
        private readonly ProjectTabStatusService $projectTabStatusService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabStatusService->buildStatusPayload($project)
        );
    }
}

