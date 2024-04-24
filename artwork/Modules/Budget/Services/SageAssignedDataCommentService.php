<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Http\Requests\StoreSageAssignedDataCommentRequest;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Repositories\SageAssignedDataCommentRepository;

readonly class SageAssignedDataCommentService
{
    public function __construct(private SageAssignedDataCommentRepository $sageAssignedDataCommentRepository)
    {
    }

    public function getById(int $id): SageAssignedDataComment|null
    {
        return $this->sageAssignedDataCommentRepository->getById($id);
    }

    public function createFromRequest(StoreSageAssignedDataCommentRequest $request): SageAssignedDataComment
    {
        $sageAssignedDataComment = new SageAssignedDataComment(
            [
                'user_id' => $request->get('userId'),
                'sage_assigned_data_id' => $request->get('sageAssignedDataId'),
                'comment' => $request->get('comment')
            ]
        );

        $this->sageAssignedDataCommentRepository->save($sageAssignedDataComment);

        return $sageAssignedDataComment;
    }

    public function delete(SageAssignedDataComment $sageAssignedDataComment): void
    {
        $this->sageAssignedDataCommentRepository->delete($sageAssignedDataComment);
    }
}
