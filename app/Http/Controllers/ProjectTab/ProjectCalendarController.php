<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectCalendarController extends Controller
{
    public function __construct(
        private readonly ProjectTabCalendarService $projectTabCalendarService,
    ) {
    }

    public function show(Project $project, Request $request): JsonResponse
    {
        $atAGlance = $request->boolean('atAGlance', false);

        return response()->json(
            $this->projectTabCalendarService->buildCalendarPayload($project, $atAGlance)
        );
    }
}

