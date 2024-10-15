<?php

namespace Artwork\Modules\ShiftPlanComment\Services;

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
}