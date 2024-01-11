<?php

namespace App\Policies;

use App\Models\User;
use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(): void
    {
    }

    public function view(): void
    {
    }

    public function create(): void
    {
    }


    public function update(): void
    {
    }


    public function delete(): void
    {
    }

    public function restore(): void
    {
    }

    public function forceDelete(): void
    {
    }
}
