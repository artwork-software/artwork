<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\User;
use App\Notifications\ConflictNotification;
use App\Notifications\EventNotification;
use App\Notifications\GlobalUserNotification;
use App\Notifications\ProjectNotification;
use App\Notifications\RoomNotification;
use App\Notifications\RoomRequestNotification;
use App\Notifications\SimpleNotification;
use App\Notifications\TaskNotification;
use App\Notifications\TeamNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     *
     */
    public function index(): \Inertia\Response|\Inertia\ResponseFactory
    {
        $user = User::find(Auth::id());
        $output = [];
        foreach ($user->notifications as $notification){
            $output[$notification->type][] = $notification;
        }
        return inertia('Notifications/Show', [
            'notifications' => $output
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($user, object $notificationData): void
    {
        $notificationBody = [];
        switch($notificationData->type){
            case NotificationConstEnum::NOTIFICATION_ROOM_REQUEST:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => [
                        'id' => $notificationData->event->id,
                        'title' => $notificationData->event->title,
                    ],
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_EVENT:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new EventNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => [
                        'title' => $notificationData->task->title,
                        'deadline' => $notificationData->task->deadline
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TaskNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_SIMPLE:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new SimpleNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_PROJECT:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ProjectNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_TEAM:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'team' => [
                        'id' => $notificationData->team->id,
                        'title' => $notificationData->team->title
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TeamNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'room' => [
                        'id' => $notificationData->room->id,
                        'title' => $notificationData->room->title,
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomNotification($notificationBody));
                break;
            case NotificationConstEnum::NOTIFICATION_CONFLICT:
                $notificationBody = [
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'conflict' => $notificationData->conflict,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ConflictNotification($notificationBody));
                break;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
