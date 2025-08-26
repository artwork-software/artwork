<?php

namespace Artwork\Modules\Project\Http\Resources;

use Artwork\Modules\Checklist\Http\Resources\ChecklistIndexResource;
use Artwork\Modules\Contract\Http\Resources\ContractResource;
use Artwork\Modules\Department\Http\Resources\DepartmentIndexResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $historyArray = [];
        $historyComplete = $this->historyChanges()->all();

        foreach ($historyComplete as $history) {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'shiftDescription' => $this->shift_description,
            'number_of_participants' => $this->number_of_participants,
            'is_group' => $this->is_group,
            'group' => $this->groups,
            'sectors' => $this->sectors,
            'categories' => $this->categories,
            'genres' => $this->genres,
            'access_budget' => $this->access_budget,
            'delete_permission_users' => $this->delete_permission_users,
            'project_managers' => $this->managerUsers,
            'write_auth' => $this->writeUsers,
            'curr_user_is_related' => $this->users->contains(Auth::id()),
            'key_visual_path' => $this->key_visual_path,
            'state' => $this->status()->first(),
            'cost_center' => $this->costCenter,
            'moneySources' => $this->money_sources,
            'project_history' => $historyArray,
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'project_files' => ProjectFileResource::collection($this->project_files),
            'contracts' => ContractResource::collection($this->contracts),
            'isMemberOfADepartment' => $this->departments
                ->contains(fn ($department) => $department->users->contains(Auth::user())),
            'public_checklists' => ChecklistIndexResource::collection(
                $this->checklists->where('private', false)->filter(function ($checklist) {
                    $userId = Auth::id();
                    // Prüfen, ob der Benutzer in den Checklistenbenutzern ist
                    $isInChecklistUsers = $checklist->users->contains('id', $userId);

                    // Prüfen, ob der Benutzer in den Aufgabenbenutzern ist
                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                        return $task->task_users->contains('id', $userId);
                    });

                    // Prüfen, ob der Benutzer der Ersteller der Checkliste ist
                    $isCreator = $checklist->user_id === $userId;

                    //Prüfen, ob der Benutzer im Projektteam ist
                    $isInProjectTeam = $checklist->project->users->contains('id', $userId);

                    return $isInChecklistUsers || $isInTaskUsers || $isCreator || $isInProjectTeam;
                })
            )->resolve(),
            'private_checklists' => ChecklistIndexResource::collection(
                $this->checklists->where('private', true)->filter(function ($checklist) {
                    $userId = Auth::id();
                    // Prüfen, ob der Benutzer in den Checklistenbenutzern ist
                    $isInChecklistUsers = $checklist->users->contains('id', $userId);

                    // Prüfen, ob der Benutzer in den Aufgabenbenutzern ist
                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                        return $task->task_users->contains('id', $userId);
                    });

                    // Prüfen, ob der Benutzer der Ersteller der Checkliste ist
                    $isCreator = $checklist->user_id === $userId;

                    return $isInChecklistUsers || $isInTaskUsers || $isCreator;
                })
            )->resolve(),
            'comments' => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'text' => $comment->text,
                'created_at' => $comment->created_at->format('d.m.Y, H:i'),
                'user' => $comment->user
            ]),
            'shift_relevant_event_types' => $this->shiftRelevantEventTypes()->get(),
            'shift_contacts' => $this->shift_contact()->get(),
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::without(['contacts'])->get(),
        ];
    }
}
