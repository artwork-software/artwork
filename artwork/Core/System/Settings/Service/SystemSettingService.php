<?php

namespace Artwork\Core\System\Settings\Service;

use Artwork\Core\System\Settings\Model\SystemSetting;
use Artwork\Core\System\Settings\Repository\SystemSettingRepository;
use Artwork\Core\System\Settings\SystemSettingKeys;

class SystemSettingService
{
    public function __construct(
        private readonly SystemSettingRepository $systemSettingRepository
    ) {
    }
    
    public function getValueFor(SystemSettingKeys $key): ?SystemSetting
    {
        return $this->systemSettingRepository->findByKey($key->value);
    }
    
    public function save(SystemSetting $setting): SystemSetting
    {
        return $this->systemSettingRepository->save($setting);
    }
}