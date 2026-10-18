<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\AppApi\Http\Requests\AppStoreCommentRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\CommentService;
use Illuminate\Http\JsonResponse;

class AppProjectCommentController extends Controller
{
    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
        private readonly CommentService $commentService,
        private readonly ChangeService $changeService,
    ) {
    }

    public function store(AppStoreCommentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        // Same rule as the web (CommentController): project team or admin
        $this->authorize('createInProject', [Comment::class, $project]);

        // The shared write path stores the raw text and records the
        // "Comment added" history entry, exactly like a comment from the web.
        $comment = $this->commentService->create(
            text: $request->validated('text'),
            user: $request->user(),
            changeService: $this->changeService,
            project: $project,
            tabId: $request->validated('tab_id'),
        );

        return response()->json(
            ['comment' => $this->systemComponentService->mapComment($comment->load('user:id,first_name,last_name'))],
            201,
        );
    }
}
