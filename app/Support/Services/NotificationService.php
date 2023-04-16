<?php

namespace App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\Project;
use App\Models\Room;
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
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($user, object $notificationData, ?array $broadcastMessage = []): void
    {
        $notificationBody = [];
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
                $room = $notificationData->room->id;
                $historyArray = [];
                $historyComplete = Room::find($room)->historyChanges()->all();
                foreach ($historyComplete as $history){
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'room' => $notificationData->room,
                    'history' => $historyArray,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomNotification($notificationBody, $broadcastMessage));
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
    }
}
