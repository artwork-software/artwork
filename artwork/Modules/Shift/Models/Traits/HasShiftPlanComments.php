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

        $comments = $this->shiftPlanComments()
            ->select(['id', 'comment', 'date'])
            ->whereBetween('date', [$start, $end])
            ->get();

        $groupedComments = [];

        foreach ($comments as $comment) {
            $groupedComments[$comment->date][] = [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'date' => $comment->date,
            ];
        }

        return $groupedComments;
    }
}
