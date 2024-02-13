<?php

namespace Artwork\Modules\SageApiSettings\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;

class SageApiSettingsRepository extends BaseRepository
{
    public function getFirst(): \Artwork\Core\Database\Models\Model|\Closure
    {
        return SageApiSettings::query()->get()->first();
    }
}
