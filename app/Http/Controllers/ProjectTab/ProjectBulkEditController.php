<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabBulkEditService;
use Illuminate\Http\JsonResponse;

class ProjectBulkEditController extends Controller
{
    public function __construct(
        private readonly ProjectTabBulkEditService $projectTabBulkEditService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabBulkEditService->buildBulkEditPayload($project)
        );
    }
}

