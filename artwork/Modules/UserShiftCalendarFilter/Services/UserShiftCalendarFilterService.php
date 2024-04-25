<?php

namespace Artwork\Modules\UserShiftCalendarFilter\Services;

use Artwork\Modules\UserShiftCalendarFilter\Repositories\UserShiftCalendarFilterRepository;

readonly class UserShiftCalendarFilterService
{
    public function __construct(private UserShiftCalendarFilterRepository $userShiftCalendarFilterRepository)
    {
    }
}
