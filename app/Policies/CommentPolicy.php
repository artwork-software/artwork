<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
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

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user->id == $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user->id == $user->id;
    }

    public function restore(): void
    {
        //
    }

    public function forceDelete(): void
    {
        //
    }
}
