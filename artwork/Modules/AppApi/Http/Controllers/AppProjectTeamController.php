<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppAddTeamMemberRequest;
use Artwork\Modules\AppApi\Http\Requests\AppTeamRightsRequest;
use Artwork\Modules\AppApi\Services\AppProjectService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTeamService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Per-member team editing. The web syncs the whole roster in one request;
 * the app edits one member at a time so a phone can never clobber a
 * concurrent web edit. Both run through ProjectTeamService, which carries the
 * shared side effects (history, notifications, broadcast).
 */
class AppProjectTeamController extends Controller
{
    // Maximum picker entries per response — the app narrows via ?q= instead
    // of receiving (and silently truncating) the whole directory.
    private const PICKER_LIMIT = 30;

    public function __construct(
        private readonly AppProjectService $projectService,
        private readonly ProjectTeamService $projectTeamService,
    ) {
    }

    /** Users that are not on the project yet, for the add-member picker. */
    public function candidates(Request $request, Project $project): JsonResponse
    {
        $this->authorizeTeamEditing($project);

        $candidatesQuery = User::query()
            ->whereNotIn('id', $project->users()->select('users.id'));

        $query = trim((string) $request->query('q', ''));
        if ($query !== '') {
            $candidatesQuery->where(static function ($builder) use ($query): void {
                $builder->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            });
        }

        $total = (clone $candidatesQuery)->count();
        $users = $candidatesQuery
            ->orderBy('last_name')
            ->limit(self::PICKER_LIMIT)
            ->get(['id', 'first_name', 'last_name']);

        return response()->json([
            'users' => $users
                ->map(static fn (User $user): array => ['id' => $user->id, 'name' => $user->full_name])
                ->all(),
            'has_more' => $total > self::PICKER_LIMIT,
        ]);
    }

    public function store(AppAddTeamMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTeamEditing($project);

        $userId = (int) $request->validated('user_id');
        abort_if(
            $project->users()->whereKey($userId)->exists(),
            422,
            'This user is already part of the project team.',
        );

        $pivot = $request->pivot();
        $this->projectTeamService->applyRosterChange(
            $project,
            static fn (Project $project) => $project->users()->attach($userId, $pivot),
        );

        return response()->json(['team' => $this->projectService->serializeTeam($project)], 201);
    }

    public function update(AppTeamRightsRequest $request, Project $project, User $user): JsonResponse
    {
        $this->authorizeTeamEditing($project);

        // Partial PATCH: rights the app does not send keep their current value
        $current = $project->users()->whereKey($user->id)->firstOrFail()->pivot;
        $pivot = $request->pivot($current);
        $this->projectTeamService->applyRosterChange(
            $project,
            static fn (Project $project) => $project->users()->updateExistingPivot($user->id, $pivot),
        );

        return response()->json(['team' => $this->projectService->serializeTeam($project)]);
    }

    public function destroy(Project $project, User $user): JsonResponse
    {
        $this->authorizeTeamEditing($project);

        $this->projectTeamService->applyRosterChange(
            $project,
            static fn (Project $project) => $project->users()->detach($user->id),
        );

        return response()->json(['team' => $this->projectService->serializeTeam($project)]);
    }

    private function authorizeTeamEditing(Project $project): void
    {
        $this->authorize('view', $project);
        // Team setzen = Schreibrecht im Projekt, wie im Web (ProjectController::updateTeam).
        $this->authorize('update', $project);
    }
}
