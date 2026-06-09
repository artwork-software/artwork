<?php

namespace Artwork\Modules\WorkTime\Repositories;

use Artwork\Modules\WorkTime\Models\UserOvertime;
use Illuminate\Database\Eloquent\Collection;

class UserOvertimeRepository
{
    public function getForUser(int $userId): Collection
    {
        return UserOvertime::with('paidOutByUser:id,first_name,last_name')
            ->forUser($userId)
            ->orderByDesc('date')
            ->get();
    }

    /**
     * Open entries for a user, oldest first (FIFO order for consumption).
     */
    public function getOpenChronological(int $userId): Collection
    {
        return UserOvertime::forUser($userId)
            ->where('status', UserOvertime::STATUS_OPEN)
            ->orderBy('date')
            ->get();
    }

    public function getDashboardStats(int $userId): array
    {
        return [
            'open_minutes' => (int) UserOvertime::forUser($userId)->open()->sum('remaining_minutes'),
            'payable_minutes' => (int) UserOvertime::forUser($userId)->payable()->sum('remaining_minutes'),
            'paid_out_minutes' => (int) UserOvertime::forUser($userId)->paidOut()->sum('minutes'),
        ];
    }
}
