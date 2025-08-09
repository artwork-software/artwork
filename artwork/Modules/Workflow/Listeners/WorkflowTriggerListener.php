<?php

namespace Artwork\Modules\Workflow\Listeners;

use Artwork\Modules\Workflow\Events\WorkflowTriggered;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WorkflowTriggerListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WorkflowService $workflowService,
        private readonly WorkflowRuleService $ruleService
    ) {
    }

    public function handle(WorkflowTriggered $event): void
    {
        try {
            // Check for workflow rules that apply to this trigger
            $this->processRuleValidation($event);

            // Check for workflows that should be started
            $this->processWorkflowStart($event);
        } catch (\Exception $e) {
            logger()->error('Workflow trigger processing failed', [
                'subject_type' => get_class($event->subject),
                'subject_id' => $event->subject->id,
                'trigger_type' => $event->triggerType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function processRuleValidation(WorkflowTriggered $event): void
    {
        // Get date range for validation (can be extended based on trigger type)
        $startDate = $event->context['start_date'] ?? now();
        $endDate = $event->context['end_date'] ?? now()->addDays(1);

        $violations = $this->ruleService->validateRulesForSubject(
            $event->subject,
            $startDate,
            $endDate
        );

        if ($violations->isNotEmpty()) {
            logger()->info('Rule violations detected', [
                'subject_type' => get_class($event->subject),
                'subject_id' => $event->subject->id,
                'violations_count' => $violations->count()
            ]);
        }
    }

    private function processWorkflowStart(WorkflowTriggered $event): void
    {
        // Check if there are workflow definitions that should be triggered
        // by this event type - this would be configurable

        $applicableWorkflows = $this->getApplicableWorkflows($event);

        foreach ($applicableWorkflows as $definition) {
            if (!$event->subject->hasActiveWorkflow($definition->type)) {
                $this->workflowService->startWorkflow($definition, $event->subject);
            }
        }
    }

    private function getApplicableWorkflows(WorkflowTriggered $event): array
    {
        // For now, return empty array
        return [];
    }
}
