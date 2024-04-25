<?php

namespace Artwork\Modules\Task\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskDashboardResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass, Generic.Metrics.CyclomaticComplexity.TooHigh
    public function toArray($request): array
    {
        $deadline = $this->deadline ? Carbon::parse($this->deadline) : null;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'checklistName' => $this->checklist?->name,
            'projectName' => $this->checklist?->project?->name,
            'projectId' => $this->checklist?->project?->id,
            'description' => $this->description,
            'deadline' =>  $deadline?->format('d.m.Y, H:i'),
            'isDeadlineInFuture' => $deadline?->isFuture(),
            'done' => $this->done,
            'done_by_user' => $this->user_who_done,
            'done_at' => $this->done_at?->format('d.m.Y, H:i'),
            'done_at_dt_local' => $this->done_at?->toDateTimeLocalString(),
            'users' => $this->task_users()->get()
        ];
    }
}
