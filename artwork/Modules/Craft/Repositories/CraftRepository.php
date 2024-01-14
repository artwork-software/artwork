<?php

namespace Artwork\Modules\Craft\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Craft\Models\Craft;

class CraftRepository extends BaseRepository
{
    /**
     * @param Craft $craft
     * @param int[] $userIds
     * @return array
     */
    public function syncUsers(Craft $craft, array $userIds): array
    {
        return $craft->users()->withTimestamps()->sync($userIds);
    }

    /**
     * @param Craft $craft
     * @return int
     */
    public function detachUsers(Craft $craft): int
    {
        return $craft->users()->detach();
    }
}
