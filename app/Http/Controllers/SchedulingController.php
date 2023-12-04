<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Checklist;
use App\Models\Event;
use App\Models\Scheduling;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\NotificationService;
use Artwork\Modules\Notification\Models\GlobalNotification;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use stdClass;

class SchedulingController extends Controller
{

    protected ?NotificationService $notificationService = null;
    protected ?stdClass $notificationData = null;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->notificationData = new stdClass();
        $this->notificationData->project = new stdClass();
        $this->notificationData->task = new stdClass();
        $this->notificationData->room = new stdClass();
    }

    public function create($userId, $type, $model, $modelId): bool
    {
        $scheduling = Scheduling::where('user_id', $userId)
            ->where('type', $type)
            ->where('model', $model)
            ->where('model_id', $modelId)
            ->first();

        if(!empty($scheduling)){
            $scheduling->increment('count', 1);
        } else {
            Scheduling::create([
                'user_id' => $userId,
                'count' => 1,
                'type' => $type,
                'model' => $model,
                'model_id' => $modelId
            ]);
        }
        return true;
    }

    public function destroy(Scheduling $scheduling): void
    {
        $scheduling->delete();
    }

    public function sendDeadlineNotification(): void
    {
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TASK_REMINDER;
        // Deadline Notification
        $checklists = Checklist::all();
        $taskWithReachedDeadline = [];
        $userForNotify = [];
        foreach ($checklists as $checklist) {
            // get all task without private checklist tasks
            if (!empty($checklist->user_id)) {
                $privateChecklistTasks = $checklist->tasks()->get();
                foreach ($privateChecklistTasks as $privateChecklistTask) {
                    $user = User::find($checklist->user_id);
                    if($privateChecklistTask->deadline === null){
                        continue;
                    }
                    $deadline = new DateTime($privateChecklistTask->deadline);
                    if ($deadline <= Carbon::now()->addDay() && $deadline >= Carbon::now()) {
                        $notificationTitle = 'Deadline von ' . $privateChecklistTask->name . ' ist morgen erreicht';
                        $broadcastMessage = [
                            'id' => rand(1, 1000000),
                            'type' => 'error',
                            'message' => $notificationTitle
                        ];
                        $this->notificationService->setTitle($notificationTitle);
                        $this->notificationService->setIcon('red');
                        $this->notificationService->setPriority(2);
                        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TASK_REMINDER);
                        $this->notificationService->setBroadcastMessage($broadcastMessage);
                        $this->notificationService->setTaskId($privateChecklistTask->id);
                        $this->notificationService->setNotificationTo($user);
                        $this->notificationService->createNotification();
                    }
                    if ($deadline <= now()) {
                        $notificationTitle = $privateChecklistTask->name . ' hat ihre Deadline überschritten';
                        $broadcastMessage = [
                            'id' => rand(1, 1000000),
                            'type' => 'error',
                            'message' => $notificationTitle
                        ];
                        $this->notificationService->setTitle($notificationTitle);
                        $this->notificationService->setIcon('red');
                        $this->notificationService->setPriority(2);
                        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TASK_REMINDER);
                        $this->notificationService->setBroadcastMessage($broadcastMessage);
                        $this->notificationService->setTaskId($privateChecklistTask->id);
                        $this->notificationService->setNotificationTo($user);
                        $this->notificationService->createNotification();
                    }
                }
                continue;
            }
            $tasks = $checklist->tasks()->get();
            foreach ($tasks->where('done_at', null) as $task) {
                if($task->deadline === null){
                    continue;
                }
                $deadline = new DateTime($task->deadline);
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
            if(!array_key_exists($taskDeadline['id'], $userForNotify)) {
                continue;
            }
            foreach ($userForNotify[$taskDeadline['id']] as $userToNotify) {
                $user = User::find($userToNotify);
                if ($taskDeadline['type'] === 'DEADLINE_REACHED') {
                    $notificationTitle = $taskDeadline['title'] . ' hat die Deadline überschritten';
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('red');
                    $this->notificationService->setPriority(2);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TASK_REMINDER);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setTaskId($task->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
                if ($taskDeadline['type'] === 'DEADLINE_NOT_REACHED') {
                    $notificationTitle = 'Deadline von ' . $task->name . ' ist morgen erreicht';
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $this->notificationData->title
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('red');
                    $this->notificationService->setPriority(2);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TASK_REMINDER);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setTaskId($task->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
            }
        }
    }

    public function sendNotification(): void
    {
        $scheduleToNotify = Scheduling::where('updated_at', '<=', Carbon::now()->addMinutes(30)->setTimezone(config('app.timezone')))->get();
        $broadcastMessage = [];
        foreach ($scheduleToNotify as $schedule) {
            $user = User::find($schedule->user_id);
            switch ($schedule->type) {
                case 'TASK_ADDED':
                    $notificationTitle = $schedule->count . ' neue Aufgaben für dich';
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('green');
                    $this->notificationService->setPriority(3);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_NEW_TASK);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    break;
                case 'PROJECT_CHANGES':
                    $project = Project::find($schedule->model_id);
                    $notificationTitle = 'Es gab Änderungen an ' . $project->name;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('green');
                    $this->notificationService->setPriority(3);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setShowHistory(true);
                    $this->notificationService->setHistoryType('project');
                    $this->notificationService->setModelId($project->id);
                    $this->notificationService->setProjectId($project->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    break;
                case 'TASK_CHANGES':
                    $task = Task::find($schedule->model_id);
                    $notificationTitle = 'Änderungen an ' . @$task->name;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('blue');
                    $this->notificationService->setPriority(1);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TASK_CHANGED);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setTaskId($task->id);
                    $this->notificationService->setButtons(['showInTasks']);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    break;
                case 'ROOM_CHANGES':
                    $room = Room::find($schedule->model_id);
                    $notificationTitle = 'Änderungen an ' . @$room->name;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('green');
                    $this->notificationService->setPriority(3);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_CHANGED);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setRoomId($room->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    break;
                case 'EVENT_CHANGES':
                    $event = Event::find($schedule->model_id);
                    $notificationTitle = 'Termin geändert';
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'link',
                            'title' => $event->room()->first()->name,
                            'href' => route('rooms.show', $event->room()->first()->id)
                        ],
                        2 => [
                            'type' => 'string',
                            'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                            'href' => null
                        ],
                        3 => [
                            'type' => 'link',
                            'title' => $event->project()->first() ? $event->project()->first()->name : '',
                            'href' => $event->project()->first() ? route('projects.show.calendar', $event->project()->first()->id) : null
                        ],
                        4 => [
                            'type' => 'string',
                            'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' . Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                            'href' => null
                        ]
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('green');
                    $this->notificationService->setPriority(3);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_EVENT_CHANGED);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setShowHistory(true);
                    $this->notificationService->setHistoryType('event');
                    $this->notificationService->setModelId($event->id);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setEventId($event->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    break;
                case 'PUBLIC_CHANGES':
                    $project = Project::find($schedule->model_id);
                    $notificationTitle = 'Es gab öffentlichkeitsarbeitsrelevante Änderungen an ' . $project->name;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('green');
                    $this->notificationService->setPriority(3);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setProjectId($project->id);
                    $this->notificationService->setShowHistory(true);
                    $this->notificationService->setHistoryType('project');
                    $this->notificationService->setModelId($project->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    break;
                case 'VACATION_CHANGES':
                    // Verfügbarkeit geändert {Vorname Name}
                    $user = User::find($schedule->model_id);
                    $notificationTitle = 'Verfügbarkeit geändert';
                    $broadcastMessage = [
                        'id' => random_int(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setIcon('green');
                    $this->notificationService->setPriority(3);
                    $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_AVAILABLE);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setShowHistory(true);
                    $this->notificationService->setHistoryType('vacations');
                    $this->notificationService->setModelId($user->id);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                    $crafts = $user->crafts()->get();
                    foreach ($crafts as $craft){
                        foreach ($craft->users()->get() as $craftUser){
                            if($craftUser->id === $user->id){
                                continue;
                            }
                            $this->notificationService->setNotificationTo($craftUser);
                            $this->notificationService->createNotification();
                        }
                    }
                    break;
            }
            //$this->notificationService->create($user, $this->notificationData, $broadcastMessage);
            $schedule->delete();
        }
    }

    public function deleteOldNotifications(): void
    {
        $users = User::all();
        foreach($users as $user) {
            foreach($user->notifications as $notification) {
                $archived = Carbon::parse($notification->read_at);
                if($archived->diffInDays(Carbon::now()) >= 7) {
                    $notification->delete();
                }
            }
        }

    }

    public function deleteExpiredNotificationForAll(): void
    {
        $notificationForAll = GlobalNotification::all();
        foreach ($notificationForAll as $notification){
            if ($notification->expiration_date <= now()){
                $notification->delete();
            }
        }
    }
}
