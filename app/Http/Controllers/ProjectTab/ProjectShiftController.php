<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabShiftService;
use Illuminate\Http\JsonResponse;

class ProjectShiftController extends Controller
{
    public function __construct(
        private readonly ProjectTabShiftService $projectTabShiftService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabShiftService->buildShiftPayload($project)
        );
    }
}

