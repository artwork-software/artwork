<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabCommentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectCommentController extends Controller
{
    public function __construct(
        private readonly ProjectTabCommentService $projectTabCommentService,
    ) {
    }

    public function index(Project $project, ComponentInTab $componentInTab): JsonResponse
    {
        if ($componentInTab->component?->type !== ProjectTabComponentEnum::COMMENT_TAB->value) {
            throw new NotFoundHttpException();
        }

        return response()->json(
            $this->projectTabCommentService->buildCommentPayload($project, $componentInTab)
        );
    }

    public function all(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabCommentService->buildAllCommentsPayload($project)
        );
    }
}
