<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabBudgetInformationService;
use Illuminate\Http\JsonResponse;

class ProjectBudgetInformationController extends Controller
{
    public function __construct(
        private readonly ProjectTabBudgetInformationService $projectTabBudgetInformationService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabBudgetInformationService->buildBudgetInformationPayload($project)
        );
    }
}

