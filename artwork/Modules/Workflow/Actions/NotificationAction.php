<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;

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
            $this->sendToUser((int) $userId, (string) $message);
        }

        if (!empty($userIds)) {
            foreach ($userIds as $id) {
                $this->sendToUser((int) $id, (string) $message);
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

    /**
     * Über NotificationService wie alle anderen Absender (Einstellungen, Sammelmail, Live-Hinweis).
     * Vorher: verkettete void-Setter und ein nicht existierendes sendToUsers() – ein \Error, den
     * WorkflowService (catch \Exception) nicht abfing.
     */
    private function sendToUser(int $userId, string $message): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $title = __('Workflow notification', [], $user->language);
        $this->notificationService->clearNotificationData();
        $this->notificationService->setTitle($title);
        $this->notificationService->setDescription([
            1 => ['type' => 'string', 'title' => $message, 'href' => null],
        ]);
        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST
        );
        $this->notificationService->setIcon('workflow');
        $this->notificationService->setBroadcastMessage([
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $title,
        ]);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }
}
