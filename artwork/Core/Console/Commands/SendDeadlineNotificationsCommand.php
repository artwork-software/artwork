<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Services\ContractService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendDeadlineNotificationsCommand extends Command
{
    protected $signature = 'artwork:send-deadline-notifications';

    protected $description = 'Create Deadline Notification';

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly CarbonService $carbonService,
        private readonly ContractService $contractService,
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
                            // Zugewiesene bekommen die Erinnerung immer – auch wenn ihnen die Checkliste
                            // gehört (vorher übersprungen: eigene Aufgaben in Team-Checklisten ohne Hinweis)
                            if (in_array($taskUser->getAttribute('id'), $alreadySentUserIds)) {
                                continue;
                            }
                            $alreadySentUserIds[] = $taskUser->getAttribute('id');
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
                    if ($task->getAttribute('sent_deadline_tomorrow_notification')) {
                        continue;
                    }
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
                        $task->update(['sent_deadline_tomorrow_notification' => true]);
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
                            // Zugewiesene bekommen die Erinnerung immer – auch wenn ihnen die Checkliste
                            // gehört (vorher übersprungen: eigene Aufgaben in Team-Checklisten ohne Hinweis)
                            if (in_array($taskUser->getAttribute('id'), $alreadySentUserIds)) {
                                continue;
                            }
                            $alreadySentUserIds[] = $taskUser->getAttribute('id');
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
                        $task->update(['sent_deadline_tomorrow_notification' => true]);
                    }
                }
            }
        }

        $this->sendContractTaskDeadlineNotifications();

        return CommandAlias::SUCCESS;
    }

    /**
     * Folgeaufgaben an Verträgen haben keine Checkliste: erinnert werden ihre Zuständigen zu denselben
     * Zeitpunkten wie bei To-dos, jede Erinnerung nur einmal und nur, solange die Aufgabe offen ist.
     */
    private function sendContractTaskDeadlineNotifications(): void
    {
        $now = $this->carbonService->getNow();
        $tasks = Task::query()
            ->whereNotNull('contract_id')
            ->where('done', false)
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $now->copy()->addDay())
            ->with(['task_users', 'contract.project'])
            ->get();

        foreach ($tasks as $task) {
            $isOverdue = $task->getAttribute('deadline') <= $now;
            $sentFlag = $isOverdue ? 'sent_deadline_notification' : 'sent_deadline_tomorrow_notification';
            if ($task->getAttribute($sentFlag)) {
                continue;
            }
            $translationKey = $isOverdue
                ? 'notification.scheduling.deadline_over'
                : 'notification.scheduling.deadline_tomorrow';

            foreach ($task->getRelation('task_users')->unique('id') as $taskUser) {
                $this->sendContractTaskDeadlineNotification(
                    __(
                        $translationKey,
                        ['checklist' => $task->getAttribute('name')],
                        $taskUser->getAttribute('language')
                    ),
                    $taskUser,
                    $task
                );
            }
            $task->update([$sentFlag => true]);
        }
    }

    /**
     * Wie die To-do-Erinnerung, aber ohne „In Aufgaben ansehen“ (Vertragsaufgaben stehen nicht in der
     * Aufgabenübersicht); der Link in der Beschreibung führt zum Vertrag oder zum Dashboard.
     */
    private function sendContractTaskDeadlineNotification(string $notificationTitle, User $user, Task $task): void
    {
        $this->notificationService->clearNotificationData();
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
        $this->notificationService->setBroadcastMessage([
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $notificationTitle,
        ]);
        $this->notificationService->setTaskId($task->getAttribute('id'));
        $this->notificationService->setDescription(
            $this->contractService->contractTaskNotificationDescription($task, $user)
        );
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    private function sendDeadlineNotification(string $notificationTitle, User $user, Task $task): void
    {
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $notificationTitle,
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setTaskId($task->getAttribute('id'));
        // Erinnerung führt wie andere Aufgaben-Meldungen direkt zur Aufgabe
        $this->notificationService->setButtons(['showInTasks']);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }
}
