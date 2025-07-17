<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Repositories\UserCommentedBudgetItemsSettingRepository;

readonly class UserCommentedBudgetItemsSettingService
{
    public function __construct(
        private UserCommentedBudgetItemsSettingRepository $userCommentedBudgetItemsSettingRepository
    ) {
    }
}
