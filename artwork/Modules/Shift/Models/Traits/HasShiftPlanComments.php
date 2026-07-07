<?php

namespace Artwork\Modules\Shift\Models\Traits;

use Artwork\Modules\Shift\Models\ShiftPlanComment;
use Illuminate\Support\Carbon;

trait HasShiftPlanComments
{
// Definiere die polymorphe Beziehung
    public function shiftPlanComments()
    {
        return $this->morphMany(ShiftPlanComment::class, 'commentable');
    }

    public function getShiftPlanCommentsForPeriod(string $startDate, string $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Use already loaded relation if available, otherwise query
        if ($this->relationLoaded('shiftPlanComments')) {
            $comments = $this->shiftPlanComments->filter(function ($comment) use ($start, $end) {
                $date = Carbon::parse($comment->date);
                return $date->between($start, $end);
            });
        } else {
            $comments = $this->shiftPlanComments()
                ->select(['id', 'comment', 'date', 'created_by', 'commentable_type', 'commentable_id'])
                ->whereBetween('date', [$start, $end])
                ->get();
        }

        $groupedComments = [];

        foreach ($comments as $comment) {
            $groupedComments[$comment->date][] = [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'date' => $comment->date,
                'created_by' => $comment->created_by,
            ];
        }

        return $groupedComments;
    }
}
