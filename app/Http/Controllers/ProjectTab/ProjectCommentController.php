<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
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

    public function index(Project $project, int $componentInTab): JsonResponse
    {
        // Try to find as ComponentInTab first
        $componentInTabModel = ComponentInTab::find($componentInTab);

        // If not found, try as DisclosureComponents
        if (!$componentInTabModel) {
            $disclosureComponent = DisclosureComponents::find($componentInTab);
            if (!$disclosureComponent || $disclosureComponent->component?->type !== ProjectTabComponentEnum::COMMENT_TAB->value) {
                throw new NotFoundHttpException();
            }

            // Create a virtual ComponentInTab-like structure for disclosure components
            $componentInTabModel = new ComponentInTab();
            $componentInTabModel->id = $disclosureComponent->id;
            $componentInTabModel->component_id = $disclosureComponent->component_id;
            $componentInTabModel->setRelation('component', $disclosureComponent->component);
        } elseif ($componentInTabModel->component?->type !== ProjectTabComponentEnum::COMMENT_TAB->value) {
            throw new NotFoundHttpException();
        }

        return response()->json(
            $this->projectTabCommentService->buildCommentPayload($project, $componentInTabModel)
        );
    }

    public function all(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabCommentService->buildAllCommentsPayload($project)
        );
    }
}
