<?php

namespace Artwork\Modules\Workflow\Repositories;

use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkflowRuleViolationRepository
{
    public function create(array $data): WorkflowRuleViolation
    {
        return WorkflowRuleViolation::create($data);
    }

    public function findById(int $id): ?WorkflowRuleViolation
    {
        return WorkflowRuleViolation::find($id);
    }

    public function getViolationsForDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return WorkflowRuleViolation::whereBetween('violation_date', [$startDate, $endDate])
            ->with(['workflowRule'])
            ->get();
    }

    public function getViolationsForSubject($subject, Carbon $startDate, Carbon $endDate): Collection
    {
        return WorkflowRuleViolation::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->whereBetween('violation_date', [$startDate, $endDate])
            ->with(['workflowRule'])
            ->get();
    }

    public function getPendingViolations(): Collection
    {
        return WorkflowRuleViolation::where('status', 'pending')
            ->with(['workflowRule'])
            ->get();
    }

    public function getViolationsByStatus(string $status): Collection
    {
        return WorkflowRuleViolation::where('status', $status)
            ->with(['workflowRule'])
            ->get();
    }

    public function updateStatus(WorkflowRuleViolation $violation, string $status): bool
    {
        return $violation->update(['status' => $status]);
    }

    public function delete(WorkflowRuleViolation $violation): bool
    {
        return $violation->delete();
    }

    public function deleteOldViolations(Carbon $olderThan): int
    {
        return WorkflowRuleViolation::where('status', 'pending')
            ->whereDate('created_at', '<', $olderThan)
            ->delete();
    }
}
