<?php

namespace App\Http\Controllers;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Project\Http\Requests\StoreCommentRequest;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Services\CommentService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService,
        private readonly ProjectService $projectService
    ) {
        $this->authorizeResource(Comment::class);
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('Comments/Create');
    }

    public function store(StoreCommentRequest $request, ChangeService $changeService): JsonResponse|RedirectResponse
    {
        $project = $this->projectService->findById($request->input('project_id'));

        /** @var User $user */
        $user = Auth::user();
        $comment = null;
        if (
            $user->hasRole(RoleEnum::ARTWORK_ADMIN->value) ||
            $this->projectService->getUsersForProject($project)->contains($user) ||
            $this->projectService->isManagerForProject($user, $project)
        ) {
            $comment = $this->commentService->create(
                text: $request->text,
                user: $user,
                project: $project,
                tabId: $request->tab_id,
                changeService: $changeService
            );
        }

        if (!$comment) {
            return response()->json(['error' => 'Not authorized to create comments in this project.'], 403);
        }

        return Redirect::back();
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $comment->text = $request->input('text');
        $this->commentService->save($comment);
        return Redirect::back();
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->commentService->forceDelete($comment);
        return Redirect::back();
    }
}
