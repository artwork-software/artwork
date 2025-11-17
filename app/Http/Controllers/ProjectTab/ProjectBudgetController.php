<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabBudgetService;
use Illuminate\Http\JsonResponse;

class ProjectBudgetController extends Controller
{
    public function __construct(
        private readonly ProjectTabBudgetService $projectTabBudgetService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabBudgetService->buildBudgetPayload($project)
        );
    }
}

