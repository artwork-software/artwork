<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabDocumentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectDocumentsController extends Controller
{
    public function __construct(
        private readonly ProjectTabDocumentService $projectTabDocumentService,
    ) {
    }

    public function index(Project $project, ComponentInTab $componentInTab): JsonResponse
    {
        if ($componentInTab->component?->type !== ProjectTabComponentEnum::PROJECT_DOCUMENTS->value) {
            throw new NotFoundHttpException();
        }

        return response()->json(
            $this->projectTabDocumentService->buildDocumentPayload($project, $componentInTab)
        );
    }

    public function all(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabDocumentService->buildAllDocumentsPayload($project)
        );
    }
}


