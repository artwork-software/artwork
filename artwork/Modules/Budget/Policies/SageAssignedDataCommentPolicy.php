<?php

namespace Artwork\Modules\Budget\Policies;

use App\Models\User;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageAssignedDataCommentPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->canAny(
            [
                'can manage global project budgets',
                'write projects'
            ]
        );
    }

    public function delete(User $user, SageAssignedDataComment $sageAssignedDataComment): bool
    {
        return $user->id === $sageAssignedDataComment->user_id;
    }
}
