<?php

namespace Artwork\Modules\UserCalendarSettings\Services;

use Artwork\Modules\UserCalendarSettings\Repositories\UserCalendarSettingsRepository;

readonly class UserCalendarSettingsService
{
    public function __construct(private UserCalendarSettingsRepository $userCalendarSettingsRepository)
    {
    }
}
