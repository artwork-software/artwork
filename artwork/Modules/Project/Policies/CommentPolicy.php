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
     * Kommentieren in einem Projekt: Projektteam (inkl. Projektleitung) und alle, die das Projekt bearbeiten
     * dürfen (ProjectPolicy::update: globales Schreibrecht, Ersteller:in, zugewiesene Abteilung) – ihnen zeigt
     * die Oberfläche das Eingabefeld. Admins via Gate::before. Gemeinsame Regel für Web (CommentController)
     * und App-API — globales Leserecht allein genügt nicht.
     */
    public function createInProject(User $user, Project $project): bool
    {
        $isInTeam = $project->relationLoaded('users')
            ? $project->users->contains($user->id)
            : $project->users()->whereKey($user->id)->exists();

        return $isInTeam || $user->can('update', $project);
    }

    /**
     * Nur die Verfasser*in; über user_id, damit ein Kommentar einer gelöschten Person kein 500 auslöst.
     */
    public function update(User $user, Comment $comment): bool
    {
        return (int) $comment->user_id === $user->id;
    }

    /**
     * Nur die Verfasser*in; über user_id, damit ein Kommentar einer gelöschten Person kein 500 auslöst.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return (int) $comment->user_id === $user->id;
    }
}
