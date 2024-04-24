<?php

namespace Artwork\Modules\UserCommentedBudgetItemsSetting\Services;

use Artwork\Modules\UserCommentedBudgetItemsSetting\Repositories\UserCommentedBudgetItemsSettingRepository;

readonly class UserCommentedBudgetItemsSettingService
{
    public function __construct(
        private UserCommentedBudgetItemsSettingRepository $userCommentedBudgetItemsSettingRepository
    ) {
    }
}
