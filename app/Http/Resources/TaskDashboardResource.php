<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskDashboardResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'checklistName' => $this->checklist->name,
            'projectName' => $this->checklist->project?->name,
            'projectId' => $this->checklist->project?->id,
            'description' => $this->description,
            'deadline' => $this->deadline ? Carbon::parse($this->deadline)->format('d.m.Y, H:i') : null,
            'isDeadlineInFuture' => $this->deadline?->isFuture(),
            'done' => $this->done,
            'done_by_user' => $this->user_who_done,
            'done_at' => $this->done_at?->format('d.m.Y, H:i'),
            'done_at_dt_local' => $this->done_at?->toDateTimeLocalString(),
            'users' => $this->task_users()->get()
        ];
    }
}
