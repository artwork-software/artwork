<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Workflow\Contracts\WorkflowAction;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Artwork\Modules\Workflow\Notifications\ShiftRuleViolationNotification;

class ShiftRuleNotificationAction implements WorkflowAction
{
    public function execute(WorkflowInstance $workflowInstance, array $parameters = []): void
    {
        $subject = $workflowInstance->subject;
        
        if (!($subject instanceof WorkflowRuleViolation)) {
            return;
        }

        $rule = $subject->workflowRule;
        if (!$rule || !$rule->shouldNotifyOnViolation()) {
            return;
        }

        $usersToNotify = $this->getUsersToNotify($rule, $parameters);
        $message = $this->generateNotificationMessage($subject, $parameters);

        foreach ($usersToNotify as $user) {
            $user->notify(new ShiftRuleViolationNotification($subject, $message));
        }
    }

    public function canExecute(WorkflowInstance $workflowInstance, array $parameters = []): bool
    {
        return $workflowInstance->subject instanceof WorkflowRuleViolation;
    }

    public function getName(): string
    {
        return 'shift_rule_notification';
    }

    private function getUsersToNotify($rule, array $parameters): \Illuminate\Support\Collection
    {
        $userIds = $parameters['user_ids'] ?? [];
        
        // Hole Benutzer die für die Regel benachrichtigt werden sollen
        $ruleUsers = $rule->usersToNotify;
        
        // Zusätzliche Benutzer aus Parametern
        if (!empty($userIds)) {
            $additionalUsers = User::whereIn('id', $userIds)->get();
            $ruleUsers = $ruleUsers->merge($additionalUsers);
        }

        return $ruleUsers->unique('id');
    }

    private function generateNotificationMessage(WorkflowRuleViolation $violation, array $parameters): string
    {
        $customMessage = $parameters['message'] ?? null;
        
        if ($customMessage) {
            return $customMessage;
        }

        $rule = $violation->workflowRule;
        $violationData = $violation->violation_data;
        
        return "Regelverstoß erkannt: {$rule->name} am {$violation->violation_date}. " . 
               ($violationData['message'] ?? 'Details siehe Schichtplan.');
    }
}