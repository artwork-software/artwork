<?php

namespace Artwork\Modules\ModuleSettings\Services;

use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\ModuleSettings\Repositories\ModuleSettingsRepository;

class ModuleSettingsService
{
    public function __construct(
        private readonly ModuleSettingsRepository $moduleSettingsRepository
    ) {
    }

    public function getModuleSettings(): ModuleSettings
    {
        return $this->moduleSettingsRepository->getModuleSettings();
    }

    public function update(string $menu, bool $enabled): ModuleSettings
    {
        return $this->moduleSettingsRepository->setMenuEnabled($menu, $enabled);
    }

    public function isModuleVisible(string $moduleSetting): bool
    {
        return $this->getModuleSettings()->{$moduleSetting};
    }
}
