<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ShiftRuleViolationRepository extends BaseRepository
{
    public function getNewModelInstance(): ShiftRuleViolation
    {
        return new ShiftRuleViolation();
    }

    public function getNewModelQuery(): Builder
    {
        return ShiftRuleViolation::query();
    }

    public function getActiveWithRelations(): Collection
    {
        return ShiftRuleViolation::with(['shiftRule', 'user', 'shift', 'createdByUser'])
            ->where('status', 'active')
            ->orderBy('violation_date', 'desc')
            ->get();
    }

    public function getActiveForDateRange(string $startDate, string $endDate, ?array $userIds = null): Collection
    {
        $query = ShiftRuleViolation::with(['shiftRule:id,name,description,warning_color,default_compensation_days,default_compensation_deadline_days'])
            ->whereBetween('violation_date', [$startDate, $endDate])
            ->where('status', 'active');

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    public function createViolation(array $attributes): ShiftRuleViolation
    {
        return ShiftRuleViolation::create($attributes);
    }

    public function resolve(ShiftRuleViolation $violation, ?int $userId = null): void
    {
        $this->update($violation, [
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function ignore(ShiftRuleViolation $violation, ?int $userId = null, ?string $ignoreReason = null): void
    {
        $this->update($violation, [
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'ignore_reason' => $ignoreReason,
        ]);
    }

    public function getUnprocessedViolationsForUser(int $userId): Collection
    {
        return ShiftRuleViolation::with(['shiftRule'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNull('compensation_days')
            ->orderByDesc('violation_date')
            ->get();
    }
}
