<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalTabReviewService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Interne Sicht auf die Externen eines Tabs: Status (in Bearbeitung/abgesendet/bestätigt/zurückgegeben)
 * neben dem Einladen-Knopf, plus Bestätigen und Zurückgeben.
 */
class ExternalTabReviewController extends Controller
{
    public function __construct(
        private readonly ExternalTabReviewService $reviewService,
        private readonly ExternalAccessSettingsResolver $settingsResolver,
    ) {
    }

    public function index(Request $request, Project $project, ProjectTab $projectTab): JsonResponse
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $project), 403);

        if (!$this->settingsResolver->isEnabled()) {
            return response()->json(['externals' => []]);
        }

        return response()->json([
            'externals' => $this->reviewService->scopesFor($project, $projectTab)
                ->map(fn (ExternalAccessScope $scope) => $this->reviewService->serialize($scope, $user, $project))
                ->values(),
        ]);
    }

    public function confirm(
        Request $request,
        Project $project,
        ProjectTab $projectTab,
        ExternalAccessScope $scope,
    ): JsonResponse {
        $user = $this->authorizeReview($request, $project, $projectTab, $scope);

        $scope = $this->reviewService->confirm($scope, $user);

        return response()->json([
            'external' => $this->reviewService->serialize(
                $scope->load(['externalAccess', 'reviewedBy']),
                $user,
                $project,
            ),
        ]);
    }

    public function returnForRevision(
        Request $request,
        Project $project,
        ProjectTab $projectTab,
        ExternalAccessScope $scope,
    ): JsonResponse {
        $user = $this->authorizeReview($request, $project, $projectTab, $scope);
        $validated = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        $scope = $this->reviewService->returnForRevision($scope, $user, $validated['comment'] ?? null);

        return response()->json([
            'external' => $this->reviewService->serialize(
                $scope->load(['externalAccess', 'reviewedBy']),
                $user,
                $project,
            ),
        ]);
    }

    private function authorizeReview(
        Request $request,
        Project $project,
        ProjectTab $projectTab,
        ExternalAccessScope $scope,
    ): User {
        abort_unless($this->settingsResolver->isEnabled(), 404);
        abort_unless(
            (int) $scope->project_id === (int) $project->id && (int) $scope->project_tab_id === (int) $projectTab->id,
            404,
        );

        $user = $this->user($request);
        abort_unless($this->reviewService->canReview($user, $scope, $project), 403);

        return $user;
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
