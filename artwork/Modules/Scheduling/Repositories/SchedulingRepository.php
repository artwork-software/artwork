<?php

namespace Artwork\Modules\Scheduling\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Scheduling\Models\Scheduling;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

readonly class SchedulingRepository extends BaseRepository
{
    public function getByUserIdAndTypeAndModelAndModelId(
        int $userId,
        string $type,
        string $model,
        int $modelId
    ): Scheduling|null {
        return Scheduling::query()
            ->byUserId($userId)
            ->byType($type)
            ->byModel($model)
            ->byModelId($modelId)
            ->first();
    }

    public function getAllWhereUpdatedAtLowerOrEqualThan(Carbon $carbon): Collection
    {
        return Scheduling::query()
            ->byUpdatedAtLowerOrEqualThan($carbon)
            ->get();
    }
}
