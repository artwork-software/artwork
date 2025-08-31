<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Workflow\Actions\WorkflowAction;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Artwork\Modules\Workflow\Notifications\ShiftRuleViolationNotification;

class ShiftRuleNotificationAction implements WorkflowAction
{
    public function execute(WorkflowInstance $workflowInstance, array $parameters = []): void
    {
        $subject = $workflowInstance->subject;
        
        if (!($subject instanceof ShiftRuleViolation)) {
            return;
        }

        $rule = $subject->shiftRule;
        if (!$rule || !$rule->is_active) {
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
        return $workflowInstance->subject instanceof ShiftRuleViolation;
    }

    public function getName(): string
    {
        return 'shift_rule_notification';
    }

    private function getUsersToNotify($rule, array $parameters): \Illuminate\Support\Collection
    {
        $userIds = $parameters['user_ids'] ?? [];
        
        // Hole Benutzer die für die Regel benachrichtigt werden sollen
        $ruleUsers = $rule->usersToNotify ?? collect([]);
        
        // Zusätzliche Benutzer aus Parametern
        if (!empty($userIds)) {
            $additionalUsers = User::whereIn('id', $userIds)->get();
            $ruleUsers = $ruleUsers->merge($additionalUsers);
        }

        return $ruleUsers->unique('id');
    }

    private function generateNotificationMessage(ShiftRuleViolation $violation, array $parameters): string
    {
        $customMessage = $parameters['message'] ?? null;
        
        if ($customMessage) {
            return $customMessage;
        }

        $rule = $violation->shiftRule;
        $violationData = $violation->violation_data;
        
        return "Regelverstoß erkannt: {$rule->name} am {$violation->violation_date}. " . 
               ($violationData['message'] ?? 'Details siehe Schichtplan.');
    }
}