<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class SageAssignedDataCommentRepository extends BaseRepository
{
    public function __construct(private readonly SageAssignedDataComment $sageAssignedDataComment)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->sageAssignedDataComment->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->sageAssignedDataComment->newModelQuery();
    }

    public function getById(int $id): SageAssignedDataComment|null
    {
        return SageAssignedDataComment::find($id);
    }
}
