<?php

namespace App\Policies;

use App\Models\ChecklistTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChecklistTemplatePolicy
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
        return $user->can('view checklist_templates');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ChecklistTemplate $checklistTemplate)
    {
        return $user->can('view checklist_templates');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create checklist_templates');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ChecklistTemplate $checklistTemplate)
    {
        return $user->can('update checklist_templates');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ChecklistTemplate $checklistTemplate)
    {
        return $user->can('delete checklist_templates');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ChecklistTemplate $checklistTemplate)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ChecklistTemplate $checklistTemplate)
    {
        //
    }
}
