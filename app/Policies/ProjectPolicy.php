<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Project $project)
    {
        $isTeamMember = false;
        foreach ($project->departments as $department) {
            if($department->users->contains($user->id)) {
                $isTeamMember = true;
            }
        }
        return ($user->projects->contains($project->id) || $project->users->contains($user->id) || $isTeamMember || $user->can(PermissionNameEnum::PROJECT_VIEW->value));
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can(PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function createProperties(User $user,Project $project)
    {

        $isTeamMember = false;
        foreach ($project->departments as $department) {
            if($department->users->contains($user->id)) {
                $isTeamMember = true;
            }
        }
        $isCreator = false;
        foreach($project->events as $event){
            if($event->user_id === $user->id){
                $isCreator = true;
            }
        }

        return $user->can('create_and_edit_projects')
            || $project->users->contains($user->id)
            || $isTeamMember
            || (bool)$user->projects()?->find($project->id)?->pivot?->is_manager === true
            || $isCreator;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Project $project)
    {
        foreach ($project->departments as $department) {
            if($department->users->contains($user->id)) {
                return true;
            }
        }
        $isCreator = false;
        foreach($project->events as $event){
            if($event->created_by->id === $user->id){
                $isCreator = true;
            }
        }
        return $user->can(PermissionNameEnum::PROJECT_UPDATE->value);
            //&& (($user->projects->contains($project->id) && $project->users->contains($user->id)));
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Project $project)
    {
        /*
        foreach ($project->departments as $department) {
            if($department->users->contains($user->id)) {
                return true;
            }
        }
        */
        $isCreator = false;
        foreach($project->events as $event){
            if($event->created_by->id === $user->id){
                $isCreator = true;
            }
        }
        return $user->can(PermissionNameEnum::PROJECT_DELETE->value) || $isCreator;
           // && (($user->projects->contains($project->id) && $project->users->contains($user->id)));
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Project $project)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Project $project)
    {
        //
    }
}
