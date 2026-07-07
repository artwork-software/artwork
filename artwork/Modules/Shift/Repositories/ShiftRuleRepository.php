<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ShiftRuleRepository extends BaseRepository
{
    public function getNewModelInstance(): ShiftRule
    {
        return new ShiftRule();
    }

    public function getNewModelQuery(): Builder
    {
        return ShiftRule::query();
    }

    public function getAllWithRelations(array $with = ['usersToNotify', 'contracts']): Collection
    {
        return ShiftRule::with($with)->get();
    }

    public function getActive(array $columns = ['*']): Collection
    {
        return ShiftRule::where('is_active', true)->get($columns);
    }

    public function createRule(array $attributes): ShiftRule
    {
        return ShiftRule::create($attributes);
    }
}
