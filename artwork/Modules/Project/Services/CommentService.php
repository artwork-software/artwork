<?php

namespace Artwork\Modules\Project\Services;

use App\Models\Contract;
use App\Models\MoneySourceFile;
use App\Models\User;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Repositories\CommentRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CommentService
{
    public function __construct(private CommentRepository $commentRepository)
    {
    }

    public function create(
        string $text,
        User $user,
        ChangeService $changeService,
        ?Project $project = null,
        ?ProjectFile $projectFile = null,
        ?MoneySourceFile $moneySourceFile = null,
        ?Contract $contract = null,
        ?int $tabId = null
    ): Comment {
        $comment = new Comment();
        $comment->text = $text;
        $comment->user()->associate($user);
        $comment->tab_id = $tabId;
        if ($project) {
            $comment->project()->associate($project);
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Comment added')
            );
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

    public function delete(Comment $comment, ChangeService $changeService): void
    {
        $changeService->saveFromBuilder(
            $changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($comment->project->id)
                ->setTranslationKey('Comment deleted')
        );

        $comment->delete();
    }

    public function deleteAll(array|Collection $comments, ChangeService $changeService): void
    {
        /** @var Comment $comment */
        foreach ($comments as $comment) {
            $this->delete($comment, $changeService);
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
