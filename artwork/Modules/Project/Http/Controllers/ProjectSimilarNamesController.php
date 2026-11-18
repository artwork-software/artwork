<?php

namespace Artwork\Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Http\Requests\ProjectSimilarNamesRequest;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectTabRepository;
use Artwork\Modules\Project\Services\ProjectNameSimilarityService;
use Illuminate\Http\JsonResponse;

/**
 * Hinweis im Anlage-Modal: bestehende Projekte und Projektgruppen mit gleichem oder ähnlichem Namen.
 * Namen sieht jede Person mit Anlagerecht (wie in der Projektsuche); verlinkt werden nur Projekte,
 * die sie öffnen darf.
 */
class ProjectSimilarNamesController extends Controller
{
    public function __construct(
        private readonly ProjectNameSimilarityService $similarityService,
        private readonly ProjectTabRepository $projectTabRepository,
    ) {
    }

    public function __invoke(ProjectSimilarNamesRequest $request): JsonResponse
    {
        $user = $request->user();
        $matches = $this->similarityService->findSimilar((string) $request->validated('name'));
        if ($matches->isEmpty()) {
            return response()->json(['matches' => []]);
        }

        Project::loadFirstAndLastEventDates($matches->pluck('project'));
        $projectTabId = ($this->projectTabRepository->getDefaultOrFirstProjectTab($user)
            ?? $this->projectTabRepository->getDefaultOrFirstProjectTab())?->getKey();

        return response()->json([
            'matches' => $matches->map(static function (array $match) use ($user, $projectTabId): array {
                /** @var Project $project */
                $project = $match['project'];
                $dates = $project->first_and_last_event_date;
                $canView = $projectTabId !== null && $user->can('view', $project);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'is_group' => (bool) $project->is_group,
                    'marked_as_done' => (bool) $project->marked_as_done,
                    'match' => $match['match'],
                    'first_event_date' => $dates['first_event_date'] ?? null,
                    'last_event_date' => $dates['last_event_date'] ?? null,
                    'url' => $canView
                        ? route('projects.tab', ['project' => $project->id, 'projectTab' => $projectTabId])
                        : null,
                ];
            })->all(),
        ]);
    }
}
