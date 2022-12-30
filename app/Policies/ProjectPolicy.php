<?php

namespace App\Policies;

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
        return ($user->projects->contains($project->id) || $project->users->contains($user->id) || $isTeamMember || $user->can('view projects'));
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return ($user->can("create and edit projects") || $user->can("admin projects"));
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
            || $user->projects()->find($project->id)->pivot->is_admin == 1
            || $user->projects()->find($project->id)->pivot->is_manager == 1
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
        return ($user->can('update projects') || $user->projects()->find($project->id)->pivot->is_admin == 1) ||$isCreator;
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
        return $user->can('delete projects') || $isCreator;
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
