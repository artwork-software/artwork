<?php

namespace Artwork\Modules\Craft\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Eloquent\Collection;

class CraftRepository extends BaseRepository
{
    /**
     * @return array<int, mixed>
     */
    public function syncUsers(Craft $craft, array $userIds): array
    {
        return $craft->users()->withTimestamps()->sync($userIds);
    }

    public function detachUsers(Craft $craft): int
    {
        return $craft->users()->detach();
    }

    public function getAll(): Collection
    {
        return Craft::all();
    }
}
