<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection as SupportCollection;

class ColumnRepository extends BaseRepository
{
    public function __construct(private readonly Column $column)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->column->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->column->newModelQuery();
    }

    public function getColumnsGroupedByTableId(): SupportCollection
    {
        return $this->getNewModelQuery()->get()->groupBy('table_id');
    }
}
