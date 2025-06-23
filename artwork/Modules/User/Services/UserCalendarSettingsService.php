<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Repositories\UserCalendarSettingsRepository;

readonly class UserCalendarSettingsService
{
    public function __construct(private UserCalendarSettingsRepository $userCalendarSettingsRepository)
    {
    }
}
