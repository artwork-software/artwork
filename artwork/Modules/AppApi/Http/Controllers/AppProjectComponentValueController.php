<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppUpdateComponentValueRequest;
use Artwork\Modules\Project\Services\ProjectComponentValueService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\JsonResponse;

class AppProjectComponentValueController extends Controller
{
    public function __construct(
        private readonly ProjectComponentValueService $componentValueService,
    ) {
    }

    public function update(
        AppUpdateComponentValueRequest $request,
        Project $project,
        ProjectTab $projectTab,
        Component $component,
    ): JsonResponse {
        $user = $request->user();

        $this->authorize('view', $project);
        abort_unless($projectTab->visibleForUser($user), 403, 'You are not allowed to view this tab.');
        abort_unless(
            $this->componentBelongsToTab($component, $projectTab),
            404,
            'This component does not belong to the requested tab.',
        );

        $type = ProjectTabComponentEnum::tryFrom((string) $component->type);
        abort_unless(
            $type?->isAppWritable() ?? false,
            403,
            'This component type cannot be edited in the app.',
        );

        abort_unless(
            $user->can('writeComponent', [$project, $component]),
            403,
            'You are not allowed to edit this component.',
        );

        $value = $this->componentValueService->updateValue(
            $project,
            $component,
            $request->validated('data'),
        );

        return response()->json([
            'component_id' => $component->id,
            'value' => $value->data ?: null,
        ]);
    }

    /**
     * Defense in depth against cross-tab manipulation: the component must live
     * in the requested tab, either directly or as a child of one of the tab's
     * disclosure components.
     */
    private function componentBelongsToTab(Component $component, ProjectTab $projectTab): bool
    {
        if (
            ComponentInTab::query()
                ->where('component_id', $component->id)
                ->where('project_tab_id', $projectTab->id)
                ->exists()
        ) {
            return true;
        }

        return DisclosureComponents::query()
            ->where('component_id', $component->id)
            ->whereIn(
                'disclosure_id',
                ComponentInTab::query()
                    ->where('project_tab_id', $projectTab->id)
                    ->select('component_id'),
            )
            ->exists();
    }
}
