<?php

namespace App\Policies;

use App\Models\User;
use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectFilePolicy
{
    use HandlesAuthorization;
}
