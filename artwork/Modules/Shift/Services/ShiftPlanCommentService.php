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
            $updateData = ['comment' => $comment];
            if ($commentModel->created_by === null) {
                $updateData['created_by'] = auth()->id();
            }
            $commentModel->update($updateData);
        } else {
            $modelInstance->shiftPlanComments()->create([
                'comment' => $comment,
                'date' => $date,
                'created_by' => auth()->id(),
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
            $updateData = ['comment' => $comment];
            if ($commentModel->created_by === null) {
                $updateData['created_by'] = auth()->id();
            }
            $commentModel->update($updateData);
        } else {
            $modelInstance->shiftPlanComments()->create([
                'comment' => $comment,
                'date' => $date,
                'created_by' => auth()->id(),
            ]);
        }
    }
}
