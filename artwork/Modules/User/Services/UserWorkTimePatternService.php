<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\UserWorkTimePattern;
use Artwork\Modules\User\Repositories\UserWorkTimePatternRepository;

class UserWorkTimePatternService
{
    public function __construct(
        protected UserWorkTimePatternRepository $userWorkTimePatternRepository
    ) {
    }

    public function create(array $data): UserWorkTimePattern
    {
        return $this->userWorkTimePatternRepository->create($data);
    }

    public function update(UserWorkTimePattern $userWorkTimePattern, array $data): UserWorkTimePattern
    {
        return $this->userWorkTimePatternRepository->update($userWorkTimePattern, $data);
    }
}
