<?php

namespace Artwork\Modules\Shift\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftQualificationPolicy
{
    use HandlesAuthorization;

    public function create(): bool
    {
        //only admins - see AuthServiceProvider boot method "Gate::before"
        //if this policy is called the given user is not an admin, so we return false
        return false;
    }

    public function update(): bool
    {
        //only admins - see AuthServiceProvider boot method "Gate::before"
        //if this policy is called the given user is not an admin, so we return false
        return false;
    }
}
