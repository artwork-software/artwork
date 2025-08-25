<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\User\Models\User;

class NotificationAction implements WorkflowAction
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function execute(WorkflowInstance $workflowInstance, array $parameters = []): void
    {
        $message = $parameters['message'] ?? 'Workflow notification';
        $userId = $parameters['user_id'] ?? null;
        $userIds = $parameters['user_ids'] ?? [];

        if ($userId) {
            $this->sendToUser($userId, $message, $workflowInstance);
        }

        if (!empty($userIds)) {
            foreach ($userIds as $id) {
                $this->sendToUser($id, $message, $workflowInstance);
            }
        }
    }

    public function canExecute(WorkflowInstance $workflowInstance, array $parameters = []): bool
    {
        return !empty($parameters['message']) &&
               (!empty($parameters['user_id']) || !empty($parameters['user_ids']));
    }

    public function getName(): string
    {
        return 'notification';
    }

    private function sendToUser(int $userId, string $message, WorkflowInstance $workflowInstance): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $this->notificationService->setTitle('Workflow Benachrichtigung')
            ->setDescription($message)
            ->setNotificationConstEnum(\Artwork\Modules\Notification\Enums\NotificationEnum::NOTIFICATION_WORKFLOW)
            ->setButtons([
                ['type' => 'success', 'text' => 'OK']
            ])
            ->setIcon('workflow')
            ->sendToUsers($user);
    }
}
