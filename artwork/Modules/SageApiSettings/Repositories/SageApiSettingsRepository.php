<?php

namespace Artwork\Modules\SageApiSettings\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;

readonly class SageApiSettingsRepository extends BaseRepository
{
    public function getFirst(): SageApiSettings|null
    {
        /** @var SageApiSettings|null $sageApiSettings */
        $sageApiSettings = SageApiSettings::query()->first();

        return $sageApiSettings;
    }
}
