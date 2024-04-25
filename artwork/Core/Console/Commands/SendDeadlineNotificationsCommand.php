<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendDeadlineNotificationsCommand extends Command
{
    protected $signature = 'artwork:send-deadline-notifications';

    protected $description = 'Create Deadline Notification';

    /**
     * @throws Exception
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function handle(NotificationService $notificationService): int
    {
        $checklists = Checklist::all();
        $taskWithReachedDeadline = [];
        $userForNotify = [];
        foreach ($checklists as $checklist) {
            // get all task without private checklist tasks
            if (!empty($checklist->user_id)) {
                $privateChecklistTasks = $checklist->tasks()->get();
                foreach ($privateChecklistTasks as $privateChecklistTask) {
                    $user = User::find($checklist->user_id);
                    if ($privateChecklistTask->deadline === null) {
                        continue;
                    }
                    $deadline = Carbon::parse($privateChecklistTask->deadline);
                    if ($deadline <= Carbon::now()->addDay() && $deadline >= Carbon::now()) {
                        $notificationTitle = __(
                            'notification.scheduling.deadline_tomorrow',
                            ['checklist' => $privateChecklistTask->name],
                            $user->language
                        );
                        $broadcastMessage = [
                            'id' => rand(1, 1000000),
                            'type' => 'error',
                            'message' => $notificationTitle
                        ];
                        $notificationService->setTitle($notificationTitle);
                        $notificationService->setIcon('red');
                        $notificationService->setPriority(2);
                        $notificationService
                            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
                        $notificationService->setBroadcastMessage($broadcastMessage);
                        $notificationService->setTaskId($privateChecklistTask->id);
                        $notificationService->setNotificationTo($user);
                        $notificationService->createNotification();
                    }
                    if ($deadline <= now()) {
                        $notificationTitle = __(
                            'notification.scheduling.deadline_over',
                            ['checklist' => $privateChecklistTask->name],
                            $user->language
                        );
                        $broadcastMessage = [
                            'id' => rand(1, 1000000),
                            'type' => 'error',
                            'message' => $notificationTitle
                        ];
                        $notificationService->setTitle($notificationTitle);
                        $notificationService->setIcon('red');
                        $notificationService->setPriority(2);
                        $notificationService
                            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
                        $notificationService->setBroadcastMessage($broadcastMessage);
                        $notificationService->setTaskId($privateChecklistTask->id);
                        $notificationService->setNotificationTo($user);
                        $notificationService->createNotification();
                    }
                }
                continue;
            }
            $tasks = $checklist->tasks()->get();
            foreach ($tasks->where('done_at', null) as $task) {
                if ($task->deadline === null) {
                    continue;
                }
                $deadline = Carbon::parse($task->deadline);
                if ($deadline <= now()) {
                    // create array with deadline reached tasks
                    $taskWithReachedDeadline[$task->id] = [
                        'type' => 'DEADLINE_REACHED',
                        'id' => $task->id,
                        'title' => $task->name,
                        'deadline' => $deadline
                    ];
                }
                if ($deadline <= Carbon::now()->addDay() && $deadline >= Carbon::now()) {
                    $taskWithReachedDeadline[$task->id] = [
                        'type' => 'DEADLINE_NOT_REACHED',
                        'id' => $task->id,
                        'title' => $task->name,
                        'deadline' => $deadline
                    ];
                }
                $users = $checklist->users()->get();
                foreach ($users as $user) {
                    $userForNotify[$task->id][$user->id] = $user->id;
                }
            }
        }
        foreach ($taskWithReachedDeadline as $taskDeadline) {
            // guard for tasks without teams
            if (!array_key_exists($taskDeadline['id'], $userForNotify)) {
                continue;
            }
            foreach ($userForNotify[$taskDeadline['id']] as $userToNotify) {
                $user = User::find($userToNotify);
                if ($taskDeadline['type'] === 'DEADLINE_REACHED') {
                    $notificationTitle = __(
                        'notification.scheduling.deadline_over',
                        ['checklist' => $taskDeadline['title']],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('red');
                    $notificationService->setPriority(2);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setTaskId($task->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                }
                if ($taskDeadline['type'] === 'DEADLINE_NOT_REACHED') {
                    $notificationTitle = __(
                        'notification.scheduling.deadline_tomorrow',
                        ['checklist' => $task->name],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('red');
                    $notificationService->setPriority(2);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_REMINDER);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setTaskId($task->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                }
            }
        }

        return CommandAlias::SUCCESS;
    }
}
