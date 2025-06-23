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


        // find the userWorkTimePattern in users and update the work time in the user model
        $userWorkTimePattern->userWorkTime()->update([
            'monday' => $data['monday'] ?? '00:00:00',
            'tuesday' => $data['tuesday'] ?? '00:00:00',
            'wednesday' => $data['wednesday'] ?? '00:00:00',
            'thursday' => $data['thursday'] ?? '00:00:00',
            'friday' => $data['friday'] ?? '00:00:00',
            'saturday' => $data['saturday'] ?? '00:00:00',
            'sunday' => $data['sunday'] ?? '00:00:00'
        ]);


        return $userWorkTimePattern;
    }
}
