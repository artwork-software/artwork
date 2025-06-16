<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Modules\User\Models\UserWorkTimePattern;

class UserWorkTimePatternRepository
{
    public function create(array $data): UserWorkTimePattern
    {
        return UserWorkTimePattern::create($data);
    }

    public function update(UserWorkTimePattern $userWorkTimePattern, array $data): UserWorkTimePattern
    {
        $userWorkTimePattern->update($data);
        return $userWorkTimePattern;
    }
}
