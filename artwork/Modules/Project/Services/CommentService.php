<?php

namespace Artwork\Modules\Project\Services;

use App\Models\Contract;
use App\Models\MoneySourceFile;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Repositories\CommentRepository;

class CommentService
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly NewHistoryService $historyService
    ) {
        $this->historyService->setModel(Project::class);
    }

    public function create(
        string $text,
        User $user,
        ?Project $project = null,
        ?ProjectFile $projectFile = null,
        ?MoneySourceFile $moneySourceFile = null,
        ?Contract $contract = null,
    ): Comment {
        $comment = new Comment();
        $comment->text = $text;
        $comment->user()->associate($user);
        if ($project) {
            $comment->project()->associate($project);
            $this->historyService->createHistory($project->id, 'Comment added');
        }
        if ($projectFile) {
            $comment->project_file()->associate($projectFile);
        }
        if ($moneySourceFile) {
            $comment->money_source_file()->associate($moneySourceFile);
        }
        if ($contract) {
            $comment->contract()->associate($contract);
        }
        return $this->save($comment);
    }

    public function save(Comment $comment): Comment
    {
        $this->commentRepository->save($comment);

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $this->historyService->createHistory($comment->project->id, 'Comment deleted');
        $comment->delete();
    }
}
