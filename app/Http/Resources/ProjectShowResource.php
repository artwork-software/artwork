<?php

namespace App\Http\Resources;

use App\Enums\CalendarTimeEnum;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \App\Models\Project
 */
class ProjectShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $projectHistory = $this->project_histories()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $rooms = Room::whereHas('events', fn ($query) => $query->where('project_id', $this->id)
            ->orderBy('end_time', 'ASC')->with('event_type'))
            ->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'number_of_participants' => $this->number_of_participants,
            'cost_center' => $this->cost_center,
            'sector' => $this->sector,
            'category' => $this->category,
            'genre' => $this->genre,
            'project_admins' => $this->adminUsers,
            'project_managers' => $this->managerUsers,
            'curr_user_is_related' => $this->users->contains(Auth::id()),

            'rooms' => $rooms->map(fn (Room $room) => $request->query('calendarType') === CalendarTimeEnum::MONTHLY
                ? new RoomIndexEventMonthlyResource($room)
                : new RoomIndexEventDayResource($room),
            ),

            'events' => EventForProjectResource::collection($this->events)->resolve(),
            'users' => UserIndexResource::collection($this->users)->resolve(),
            'project_history' => ProjectHistoryResource::collection($projectHistory)->resolve(),
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),

            'project_files' => $this->project_files,


            'isMemberOfADepartment' => $this->departments->contains(fn ($department) => $department->users->contains(Auth::user())),

            'public_checklists' => ChecklistIndexResource::collection($this->checklists->whereNull('user_id'))->resolve(),

            'private_checklists' => ChecklistIndexResource::collection($this->checklists()->where('user_id', Auth::id())->get())->resolve(),

            'comments' => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'text' => $comment->text,
                'created_at' => $comment->created_at->format('d.m.Y, H:i'),
                'user' => $comment->user
            ])
        ];
    }
}
