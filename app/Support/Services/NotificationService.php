<?php

namespace App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\Department;
use App\Models\Event;
use App\Models\Project;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use App\Notifications\BudgetVerified;
use App\Notifications\ConflictNotification;
use App\Notifications\DeadlineNotification;
use App\Notifications\EventNotification;
use App\Notifications\MoneySourceNotification;
use App\Notifications\ProjectNotification;
use App\Notifications\RoomNotification;
use App\Notifications\RoomRequestNotification;
use App\Notifications\TaskNotification;
use App\Notifications\TeamNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Ramsey\Uuid\Type\Integer;

class NotificationService
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($user, object $notificationData, ?array $broadcastMessage = []): void
    {
        /*$notificationBody = [];
        $notificationBodyNew = new \stdClass();
        switch ($notificationData->type) {
            case NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_REQUEST:
                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_EVENT_CHANGED:

                $historyArray = [];
                $historyComplete = $notificationData->event->historyChanges()->all();

                foreach ($historyComplete as $history){
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }

                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'eventHistory' => $historyArray,
                    'created_by' => $notificationData->created_by,
                ];
                Notification::send($user, new EventNotification($notificationBody, $broadcastMessage));
                break;

            case NotificationConstEnum::NOTIFICATION_TASK_CHANGED:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => [
                        'title' => $notificationData->task->title,
                        'deadline' => $notificationData->task->deadline
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TaskNotification($notificationBody, $broadcastMessage));
                break;

            case NotificationConstEnum::NOTIFICATION_PROJECT:
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ProjectNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TEAM:
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'team' => [
                        'id' => $notificationData->team->id,
                        'title' => $notificationData->team->title,
                        'svg_name' => $notificationData->team->svg_name,
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TeamNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_CHANGED:
                $notificationBodyNew->groupType = 'ROOMS';
                $notificationBodyNew->type = $notificationData->type;
                $notificationBodyNew->title = $notificationData->title;
                $notificationBodyNew->room = $notificationData->room;
                $notificationBodyNew->created_by = $notificationData->created_by;

                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'room' => $notificationData->room,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomNotification($notificationBodyNew, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_CONFLICT:
            case NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT:
                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'conflict' => $notificationData->conflict,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ConflictNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK_REMINDER:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => $notificationData->task,
                ];
                Notification::send($user, new DeadlineNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_NEW_TASK:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                ];
                Notification::send($user, new TaskNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED:
                $notificationBody = [
                    'groupType' => 'BUDGET',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'created_by' => $notificationData->created_by,
                ];
                Notification::send($user, new MoneySourceNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
                $notificationBody = [
                    'groupType' => 'BUDGET',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'requested_position' => $notificationData->requested_position,
                    'project' => $notificationData->project,
                    'created_by' => $notificationData->created_by,
                    'requested_id' => $notificationData->requested_id,
                    'position' => $notificationData->position,
                    'changeType' => $notificationData->changeType
                ];
                Notification::send($user, new BudgetVerified($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT:
                $project = $notificationData->project->id;
                $historyArray = [];
                $historyComplete = Project::find($project)->historyChanges()->all();
                foreach ($historyComplete as $history){
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by,
                    'history' => $historyArray,
                ];
                Notification::send($user, new ProjectNotification($notificationBody, $broadcastMessage));
                break;
        }
        */
    }


    /**
     * This function creates room notifications
     * @param User $notificationTo
     * @param String $title
     * @param NotificationConstEnum|null $notificationConstEnum
     * @param String $icon
     * @param array $buttons
     * @param bool $showHistory
     * @param String $historyType
     * @param Integer|null $modelId
     * @param array|null $broadcastMessage
     * @param Room|null $room
     * @param Event|null $event
     * @param Project|null $project
     * @param Department|null $department
     * @param Task|null $task
     * @return void
     */
    public function createNotification(
        User $notificationTo,
        String $title,
        ?NotificationConstEnum $notificationConstEnum = null,
        String $icon = 'green',
        Array $buttons = [],
        bool $showHistory = false,
        String $historyType = '',
        Integer $modelId = null,
        ?Array $broadcastMessage,
        ?Room $room = null,
        ?Event $event = null,
        ?Project $project = null,
        ?Department $department = null,
        ?Task $task = null,
    ): void
    {
        $body = new \stdClass();
        $body->icon = $icon;
        $body->groupType = 'ROOMS';
        $body->type = $notificationConstEnum;
        $body->title = $title;
        if(!empty($room)){
            $body->room = $room->withoutRelations();
        }
        if(!empty($event)){
            $body->event = $event->withoutRelations();
            $body->eventComments = $event->comments()->orderBy('created_at', 'DESC')->get();
        }
        if(!empty($project)){
            $body->project = $project->withoutRelations();
        }
        if(!empty($department)){
            $body->department = $department->withoutRelations();
        }
        if(!empty($department)){
            $body->task = $task->withoutRelations();
        }
        $creator = User::find(Auth::id());
        if(!empty($creator)){
            $body->created_by = $creator->withoutRelations();
        }


        $body->buttons = $buttons;
        $body->showHistory = $showHistory;
        $body->historyType = $historyType;
        $body->modelId = $modelId;
        $body->created_at = Carbon::now()->translatedFormat('d.m.Y H:i');
        switch ($notificationConstEnum) {
            case NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
            case NotificationConstEnum::NOTIFICATION_ROOM_REQUEST:
                Notification::send($notificationTo, new RoomRequestNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_EVENT_CHANGED:
                Notification::send($notificationTo, new EventNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_NEW_TASK:
            case NotificationConstEnum::NOTIFICATION_TASK_CHANGED:
                Notification::send($notificationTo, new TaskNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_PROJECT:
            case NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT:
                Notification::send($notificationTo, new ProjectNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TEAM:
                Notification::send($notificationTo, new TeamNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_CHANGED:
                Notification::send($notificationTo, new RoomNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_CONFLICT:
            case NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT:
                Notification::send($notificationTo, new ConflictNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK_REMINDER:
                Notification::send($notificationTo, new DeadlineNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED:
                Notification::send($notificationTo, new MoneySourceNotification($body, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
                Notification::send($notificationTo, new BudgetVerified($body, $broadcastMessage));
                break;
                /*
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED:
                throw new \Exception('To be implemented');
                break;
            case NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED:
                throw new \Exception('To be implemented');
                break;
            case NotificationConstEnum::NOTIFICATION_REMINDER_ROOM_REQUEST:
                throw new \Exception('To be implemented');
                */
        }
    }


}
