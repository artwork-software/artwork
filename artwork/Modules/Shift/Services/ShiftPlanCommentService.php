<?php

namespace Artwork\Modules\Shift\Services;

class ShiftPlanCommentService
{
    public function addOrUpdateShiftPlanComment(
        $modelInstance,
        $comment,
        $date,
        $id = null
    ): void {
        $commentModel = $modelInstance->shiftPlanComments()->find($id);

        if ($commentModel) {
            $commentModel->update([
                'comment' => $comment,
            ]);
        } else {
            $modelInstance->shiftPlanComments()->create([
                'comment' => $comment,
                'date' => $date,
            ]);
        }
    }

    public function addOrUpdateShiftPlanCommentByModel(
        $modelInstance,
        $comment,
        $date,
    ): void {
        // check if $modelInstance has an shiftPlanComment
        $commentModel = $modelInstance->shiftPlanComments()->where('date', $date)->first();

        if ($commentModel) {
            $commentModel->update([
                'comment' => $comment,
            ]);
        } else {
            $modelInstance->shiftPlanComments()->create([
                'comment' => $comment,
                'date' => $date,
            ]);
        }
    }
}
