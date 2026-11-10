<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\Project\Services\ProjectTabDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectDocumentsController extends Controller
{
    public function __construct(
        private readonly ProjectTabDocumentService $projectTabDocumentService,
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
    ) {
    }

    public function index(Request $request, Project $project, int $componentInTab): JsonResponse
    {
        // Platzierungen in Ordnern (disclosure_components) haben eigene Ids und eine eigene
        // Tab-Auswahl; das Frontend kennzeichnet sie mit ?placement=disclosure.
        $inFolder = $request->query('placement') === 'disclosure';
        $componentInTabModel = $this->projectComponentVisibilityService->resolvePlacement(
            $request->user(),
            $componentInTab,
            $inFolder,
            ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
        );

        return response()->json(
            $this->projectTabDocumentService->buildDocumentPayload(
                $project,
                $componentInTabModel,
                $request->user(),
                $inFolder,
            )
        );
    }

    public function all(Request $request, Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabDocumentService->buildAllDocumentsPayload($project, $request->user())
        );
    }
}
