<?php

namespace Artwork\Modules\UserShiftCalendarFilter\Services;

use Artwork\Modules\UserShiftCalendarFilter\Repositories\UserCommentedBudgetItemsSettingRepository;

readonly class UserShiftCalendarFilterService
{
    public function __construct(private UserCommentedBudgetItemsSettingRepository $userShiftCalendarFilterRepository)
    {
    }
}
