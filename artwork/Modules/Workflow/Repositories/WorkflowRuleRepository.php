<?php

namespace Artwork\Modules\Workflow\Repositories;

use Artwork\Modules\Workflow\Models\WorkflowRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WorkflowRuleRepository
{
    public function create(array $data): WorkflowRule
    {
        return WorkflowRule::create($data);
    }

    public function getActiveRulesForSubject(Model $subject): Collection
    {
        return WorkflowRule::where('is_active', true)
            ->whereHas('workflowRuleAssignments', function ($query) use ($subject): void {
                $query->where('subject_type', get_class($subject))
                    ->where('subject_id', $subject->id);
            })
            ->get();
    }

    public function findById(int $id): ?WorkflowRule
    {
        return WorkflowRule::find($id);
    }

    public function update(WorkflowRule $rule, array $data): bool
    {
        return $rule->update($data);
    }

    public function delete(WorkflowRule $rule): bool
    {
        return $rule->delete();
    }

    public function getAll(): Collection
    {
        return WorkflowRule::all();
    }

    public function getActive(): Collection
    {
        return WorkflowRule::where('is_active', true)->get();
    }
}
