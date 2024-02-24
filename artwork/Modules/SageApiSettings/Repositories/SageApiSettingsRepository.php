<?php

namespace Artwork\Modules\SageApiSettings\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;

class SageApiSettingsRepository extends BaseRepository
{
    public function getFirst(): SageApiSettings|null
    {
        return SageApiSettings::query()->get()->first();
    }
}
