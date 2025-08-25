<?php

namespace Artwork\Modules\Workflow\Listeners;

use Artwork\Modules\Workflow\Events\WorkflowTriggered;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AutoStartWorkflowListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WorkflowService $workflowService
    ) {
    }

    public function handle(WorkflowTriggered $event): void
    {
        try {
            // Check if this is a ShiftRuleViolation creation event
            if ($event->subject instanceof ShiftRuleViolation && $event->triggerType === 'created') {
                $this->startViolationWorkflow($event->subject);
            }

            // Log the event for debugging
            logger()->info('Workflow trigger processed', [
                'subject_type' => get_class($event->subject),
                'subject_id' => $event->subject->id,
                'trigger_type' => $event->triggerType,
                'context' => $event->context
            ]);

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

    private function startViolationWorkflow(ShiftRuleViolation $violation): void
    {
        // Find the shift violation management workflow
        $definition = WorkflowDefinition::where('type', 'shift_violation_management')
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            logger()->info('No active shift_violation_management workflow found');
            return;
        }

        // Check if violation already has an active workflow
        if ($violation->hasActiveWorkflow('shift_violation_management')) {
            logger()->info('ShiftRuleViolation already has active workflow', [
                'violation_id' => $violation->id
            ]);
            return;
        }

        // Start the workflow
        $instance = $this->workflowService->startWorkflow($definition, $violation);

        logger()->info('Started workflow for violation', [
            'violation_id' => $violation->id,
            'workflow_instance_id' => $instance->id,
            'workflow_type' => $definition->type
        ]);
    }
}