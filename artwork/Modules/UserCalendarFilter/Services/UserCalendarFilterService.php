<?php

namespace Artwork\Modules\UserCalendarFilter\Services;

use Artwork\Modules\UserCalendarFilter\Repositories\UserCalendarFilterRepository;

readonly class UserCalendarFilterService
{
    public function __construct(private UserCalendarFilterRepository $userCalendarFilterRepository)
    {
    }
}
