<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabChecklistService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectChecklistController extends Controller
{
    public function __construct(
        private readonly ProjectTabChecklistService $projectTabChecklistService,
    ) {
    }

    public function index(Project $project, ?ComponentInTab $componentInTab = null): JsonResponse
    {
        if ($componentInTab && $componentInTab->component?->type !== ProjectTabComponentEnum::CHECKLIST->value) {
            throw new NotFoundHttpException();
        }

        return response()->json(
            $this->projectTabChecklistService->buildChecklistPayload($project, $componentInTab)
        );
    }

    public function all(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabChecklistService->buildAllChecklistsPayload($project)
        );
    }
}

