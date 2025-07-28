<?php

namespace Artwork\Modules\Workflow\Services;

use Artwork\Modules\Workflow\Contracts\WorkflowRule as WorkflowRuleContract;
use Artwork\Modules\Workflow\Models\WorkflowRule;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Artwork\Modules\Workflow\Repositories\WorkflowRuleRepository;
use Artwork\Modules\Workflow\Repositories\WorkflowRuleViolationRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WorkflowRuleService
{
    private array $ruleImplementations = [];

    public function __construct(
        private readonly WorkflowRuleRepository $workflowRuleRepository,
        private readonly WorkflowRuleViolationRepository $violationRepository
    ) {
        $this->registerDefaultRules();
    }

    public function registerRule(WorkflowRuleContract $rule): void
    {
        $this->ruleImplementations[$rule->getName()] = $rule;
    }

    public function validateRulesForSubject(
        Model $subject, 
        Carbon $startDate, 
        Carbon $endDate
    ): Collection {
        $violations = collect();
        $rules = $this->getActiveRulesForSubject($subject);

        foreach ($rules as $rule) {
            $ruleImplementation = $this->getRuleImplementation($rule->trigger_type);
            if (!$ruleImplementation) {
                continue;
            }

            $dateRange = CarbonPeriod::create($startDate, $endDate);
            
            foreach ($dateRange as $date) {
                $context = array_merge(
                    $rule->getValidationConfig(),
                    ['date' => $date]
                );

                $ruleViolations = $ruleImplementation->validate($subject, $context);
                
                foreach ($ruleViolations as $violation) {
                    $violations->push($this->createViolation($rule, $subject, $violation));
                }
            }
        }

        return $violations;
    }

    public function checkRuleViolationsForDateRange(
        Carbon $startDate, 
        Carbon $endDate
    ): Collection {
        $allViolations = collect();
        
        // Get all subjects that have active rules
        $subjects = $this->getSubjectsWithActiveRules();
        
        foreach ($subjects as $subject) {
            $violations = $this->validateRulesForSubject($subject, $startDate, $endDate);
            $allViolations = $allViolations->concat($violations);
        }
        
        return $allViolations;
    }

    public function createRule(
        string $name,
        string $triggerType,
        float $value,
        array $configuration = []
    ): WorkflowRule {
        return $this->workflowRuleRepository->create([
            'name' => $name,
            'trigger_type' => $triggerType,
            'individual_number_value' => $value,
            'configuration' => $configuration,
            'is_active' => true
        ]);
    }

    public function assignRuleToSubject(
        WorkflowRule $rule, 
        Model $subject, 
        int $assignedBy = null
    ): void {
        $rule->workflowRuleAssignments()->create([
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy
        ]);
    }

    public function getAvailableRuleTypes(): array
    {
        return array_keys($this->ruleImplementations);
    }

    public function getRuleConfiguration(string $ruleType): array
    {
        $ruleImplementation = $this->getRuleImplementation($ruleType);
        return $ruleImplementation?->getConfiguration() ?? [];
    }

    private function getActiveRulesForSubject(Model $subject): Collection
    {
        return $this->workflowRuleRepository->getActiveRulesForSubject($subject);
    }

    private function getRuleImplementation(string $triggerType): ?WorkflowRuleContract
    {
        return $this->ruleImplementations[$triggerType] ?? null;
    }

    private function createViolation(
        WorkflowRule $rule, 
        Model $subject, 
        array $violationData
    ): WorkflowRuleViolation {
        return $this->violationRepository->create([
            'workflow_rule_id' => $rule->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'violation_date' => $violationData['date'],
            'violation_data' => $violationData,
            'severity' => $violationData['severity'] ?? 'medium',
            'status' => 'pending'
        ]);
    }

    private function getSubjectsWithActiveRules(): Collection
    {
        // This would need to be implemented based on your specific models
        // For now, return empty collection
        return collect();
    }

    private function registerDefaultRules(): void
    {
        // Register all shift rule implementations
        $this->registerRule(new \Artwork\Modules\Workflow\Rules\MaxWorkingHoursOnDayRule());
        $this->registerRule(new \Artwork\Modules\Workflow\Rules\MaxConsecutiveWorkingDaysRule());
        $this->registerRule(new \Artwork\Modules\Workflow\Rules\WeeklyMaxHoursRule());
        $this->registerRule(new \Artwork\Modules\Workflow\Rules\RestTimeBeforeWorkdayRule());
        $this->registerRule(new \Artwork\Modules\Workflow\Rules\RestTimeBeforeHolidayRule());
        $this->registerRule(new \Artwork\Modules\Workflow\Rules\MinDaysBeforeCommitRule());
    }
}
