<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabShiftContactsService;
use Illuminate\Http\JsonResponse;

class ProjectShiftContactsController extends Controller
{
    public function __construct(
        private readonly ProjectTabShiftContactsService $projectTabShiftContactsService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabShiftContactsService->buildShiftContactsPayload($project)
        );
    }
}

