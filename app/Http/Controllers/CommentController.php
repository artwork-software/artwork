<?php

namespace App\Http\Controllers;

use App\Enums\RoleNameEnum;
use App\Http\Requests\StoreCommentRequest;
use Artwork\Modules\Project\Models\Comment;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\CommentService;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService,
        private readonly ProjectService $projectService
    )
    {
        $this->authorizeResource(Comment::class);
    }

    public function create(): \Inertia\Response|\Inertia\ResponseFactory
    {
        return inertia('Comments/Create');
    }

    public function store(StoreCommentRequest $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $project = $this->projectService->findById($request->input('project_id'));

        /** @var User $user */
        $user = Auth::user();
        $comment = null;
        if ($user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value) ||
            $this->projectService->getUsersForProject($project)->contains($user) ||
            $this->projectService->isManagerForProject($user, $project)
        ) {
            $comment = $this->commentService->create(
                text: $request->text, user: $user, project: $project
            );
        }

        if (!$comment) {
            return response()->json(['error' => 'Not authorized to create comments in this project.'], 403);
        }

        return Redirect::back()->with('success', 'Comment created');
    }

    public function update(Request $request, Comment $comment): \Illuminate\Http\RedirectResponse
    {
        $comment->text = $request->input('text');
        $this->commentService->save($comment);
        return Redirect::back()->with('success', 'Comment updated');
    }

    public function destroy(Comment $comment): \Illuminate\Http\RedirectResponse
    {
        $this->commentService->delete($comment);
        return Redirect::back()->with('success', 'Comment deleted');
    }
}
