<?php

namespace Artwork\Modules\ModuleSettings\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;

class ModuleSettingsRepository extends BaseRepository
{
    public function __construct(private readonly ModuleSettings $moduleSettings)
    {
    }

    public function getModuleSettings(): ModuleSettings
    {
        return $this->moduleSettings;
    }

    public function setMenuEnabled(string $menu, bool $enabled): ModuleSettings
    {
        $this->moduleSettings->{$menu} = $enabled;
        $this->moduleSettings->save();

        return $this->moduleSettings;
    }
}
