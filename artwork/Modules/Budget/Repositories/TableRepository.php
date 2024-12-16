<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class TableRepository extends BaseRepository
{
    public function __construct(private readonly Table $table)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->table->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->table->newModelQuery();
    }

    public function getAll(array $with): Collection
    {
        $query = $this->getNewModelQuery();

        if (count($with) > 0) {
            $query->with($with);
        }

        return $query->get();
    }
}
