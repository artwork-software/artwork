<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Comment $comment): bool
    {
        return $comment->project->users->contains($user->id);
    }

    public function create(): bool
    {
        return true;
    }

    /**
     * Kommentieren in einem Projekt: Projektteam (inkl. Projektleitung); Admins via Gate::before.
     * Gemeinsame Regel für Web (CommentController) und App-API — globales Leserecht allein genügt nicht.
     */
    public function createInProject(User $user, Project $project): bool
    {
        return $project->relationLoaded('users')
            ? $project->users->contains($user->id)
            : $project->users()->whereKey($user->id)->exists();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user->id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user->id === $user->id;
    }
}
