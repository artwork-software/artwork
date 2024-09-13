<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendDeadlineNotificationsCommand extends Command
{
    protected $signature = 'artwork:send-deadline-notifications';

    protected $description = 'Create Deadline Notification';

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly CarbonService $carbonService,
    ) {
        parent::__construct();
    }

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function handle(): int
    {
        $checklists = Checklist::query()->with(['user', 'users'])->get();
        foreach ($checklists as $checklist) {
            /** @var Task $task */
            foreach ($checklist->tasks()->with(['task_users'])->where('done_at', null)->get() as $task) {
                if (
                    ($deadline = $task->getAttribute('deadline')) === null ||
                    $task->getAttribute('sent_deadline_notification')
                ) {
                    continue;
                }
                $isPrivateChecklist = $checklist->getAttribute('private');
                /** @var User $checklistUser */
                $checklistUser = $checklist->getRelation('user');
                $alreadySentUserIds = [];

                if ($deadline <= ($now = $this->carbonService->getNow())) {
                    if ($isPrivateChecklist && $checklistUser) {
                        $this->sendDeadlineNotification(
                            __(
                                'notification.scheduling.deadline_over',
                                ['checklist' => $task->getAttribute('name')],
                                $checklistUser->getAttribute('language')
                            ),
                            $checklistUser,
                            $task
                        );
                        $task->update(['sent_deadline_notification' => true]);
                        continue;
                    }
                    if (!$isPrivateChecklist) {
                        foreach ($checklist->getRelation('users') as $checklistsUser) {
                            if (
                                $checklistUser &&
                                $checklistUser->getAttribute('id') === $checklistsUser->getAttribute('id')
                            ) {
                                continue;
                            }
                            $this->sendDeadlineNotification(
                                __(
                                    'notification.scheduling.deadline_over',
                                    ['checklist' => $task->getAttribute('name')],
                                    $checklistsUser->getAttribute('language')
                                ),
                                $checklistsUser,
                                $task
                            );
                            $alreadySentUserIds[] = $checklistsUser->getAttribute('id');
                        }
                        foreach ($task->getRelation('task_users') as $taskUser) {
                            if (
                                (
                                    $checklistUser &&
                                    $checklistUser->getAttribute('id') === $taskUser->getAttribute('id')
                                ) ||
                                in_array($taskUser->getAttribute('id'), $alreadySentUserIds)
                            ) {
                                continue;
                            }
                            $this->sendDeadlineNotification(
                                __(
                                    'notification.scheduling.deadline_over',
                                    ['checklist' => $task->getAttribute('name')],
                                    $taskUser->getAttribute('language')
                                ),
                                $taskUser,
                                $task
                            );
                        }
                        $task->update(['sent_deadline_notification' => true]);
                    }
                } elseif ($deadline <= $now->copy()->addDay() && $deadline >= $now) {
                    if ($isPrivateChecklist && $checklistUser) {
                        $this->sendDeadlineNotification(
                            __(
                                'notification.scheduling.deadline_tomorrow',
                                ['checklist' => $task->getAttribute('name')],
                                $checklistUser->getAttribute('language')
                            ),
                            $checklistUser,
                            $task
                        );
                        continue;
                    }
                    if (!$isPrivateChecklist) {
                        foreach ($checklist->getRelation('users') as $checklistsUser) {
                            if (
                                $checklistUser &&
                                $checklistUser->getAttribute('id') === $checklistsUser->getAttribute('id')
                            ) {
                                continue;
                            }
                            $this->sendDeadlineNotification(
                                __(
                                    'notification.scheduling.deadline_tomorrow',
                                    ['checklist' => $task->getAttribute('name')],
                                    $checklistsUser->getAttribute('language')
                                ),
                                $checklistsUser,
                                $task
                            );
                            $alreadySentUserIds[] = $checklistsUser->getAttribute('id');
                        }
                        foreach ($task->getRelation('task_users') as $taskUser) {
                            if (
                                (
                                    $checklistUser &&
                                    $checklistUser->getAttribute('id') === $taskUser->getAttribute('id')
                                ) ||
                                in_array($taskUser->getAttribute('id'), $alreadySentUserIds)
                            ) {
                                continue;
                            }
                            $this->sendDeadlineNotification(
                                __(
                                    'notification.scheduling.deadline_tomorrow',
                                    ['checklist' => $task->getAttribute('name')],
                                    $taskUser->getAttribute('language')
                                ),
                                $taskUser,
                                $task
                            );
                        }
                    }
                }
            }
        }

        return CommandAlias::SUCCESS;
    }

    private function sendDeadlineNotification(string $notificationTitle, User $user, Task $task): void
    {
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle,
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setTaskId($task->getAttribute('id'));
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }
}
