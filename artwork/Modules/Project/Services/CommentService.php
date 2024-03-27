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
use Illuminate\Database\Eloquent\Collection;

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

    public function deleteAll(array|Collection $comments): void
    {
        /** @var Comment $comment */
        foreach ($comments as $comment) {
            $this->delete($comment);
        }
    }


    public function restoreAll(array|Collection $comments): void
    {
        /** @var Comment $comment */
        foreach ($comments as $comment) {
            $this->restore($comment);
        }
    }

    public function forceDeleteAll(array|Collection $comments): void
    {
        /** @var Comment $comment */
        foreach ($comments as $comment) {
            $this->forceDelete($comment);
        }
    }

    public function restore(Comment $comment): void
    {
        //$this->historyService->createHistory($comment->project->id, 'Comment restored');
        $comment->restore();
    }

    public function forceDelete(Comment $comment): void
    {
        //$this->historyService->createHistory($comment->project->id, 'Comment force deleted');
        $comment->forceDelete();
    }
}
