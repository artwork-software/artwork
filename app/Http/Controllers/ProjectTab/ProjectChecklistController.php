<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
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

    public function index(Project $project, ?int $componentInTab = null): JsonResponse
    {
        $componentInTabModel = null;

        if ($componentInTab) {
            // Try to find as ComponentInTab first
            $componentInTabModel = ComponentInTab::find($componentInTab);

            // If not found, try as DisclosureComponents
            if (!$componentInTabModel) {
                $disclosureComponent = DisclosureComponents::find($componentInTab);
                if (!$disclosureComponent || $disclosureComponent->component?->type !== ProjectTabComponentEnum::CHECKLIST->value) {
                    throw new NotFoundHttpException();
                }

                // Create a virtual ComponentInTab-like structure for disclosure components
                $componentInTabModel = new ComponentInTab();
                $componentInTabModel->id = $disclosureComponent->id;
                $componentInTabModel->component_id = $disclosureComponent->component_id;
                $componentInTabModel->setRelation('component', $disclosureComponent->component);
            } elseif ($componentInTabModel->component?->type !== ProjectTabComponentEnum::CHECKLIST->value) {
                throw new NotFoundHttpException();
            }
        }

        return response()->json(
            $this->projectTabChecklistService->buildChecklistPayload($project, $componentInTabModel)
        );
    }

    public function all(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabChecklistService->buildAllChecklistsPayload($project)
        );
    }
}

