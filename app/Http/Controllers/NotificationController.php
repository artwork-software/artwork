<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationGroupEnum;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectShowResource;
use App\Http\Resources\RoomIndexWithoutEventsResource;
use App\Models\EventType;
use App\Models\NotificationSetting;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ConflictNotification;
use App\Notifications\DeadlineNotification;
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
use Illuminate\Support\Facades\Redirect;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     *
     */
    public function index(): \Inertia\Response|\Inertia\ResponseFactory
    {
        /** @var User $user */
        $user = Auth::user();
        $output = [];
        $outputRead = [];

        foreach ($user->notifications as $notification) {
            if($notification->read_at === null){
                $output[$notification->data['groupType']][] = $notification;
            }else{
                $outputRead[$notification->data['groupType']][] = $notification;
            }
        }

        return inertia('Notifications/Show', [
            'notifications' => $output,
            'readNotifications' => $outputRead,
            'rooms' => RoomIndexWithoutEventsResource::collection(Room::all())->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => ProjectShowResource::collection(Project::all())->resolve(),
            'notificationSettings' => $user->notificationSettings()->get()->groupBy("group_type"),
            'notificationFrequencies' => array_map(fn (NotificationFrequency $frequency) => [
                'title' => $frequency->title(),
                'value' => $frequency->value,
            ], NotificationFrequency::cases()),
            'groupTypes' => collect(NotificationGroupEnum::cases())->reduce( function($groupTypes, $type) {
                $groupTypes[$type->value] = [
                    'title' => $type->title(),
                    'description' =>$type->description(),
                ];
                return $groupTypes;
            },[]),
        ]);
    }

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
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function setOnRead(Request $request)
    {
        $user = User::find(Auth::id());
        $wantedNotification = $user->notifications->find($request->notificationId);
        $wantedNotification->read_at = now();
        $wantedNotification->save();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param NotificationSetting $setting
     */
    public function updateSetting(Request $request, NotificationSetting $setting): void
    {
        if(Auth::id() !== $setting->user_id) {
            abort(403);
        }

        $setting->update($request->only("enabled_email", "frequency", "enabled_push"));
    }

    public function toggleGroup(Request $request): void {
        Auth::user()->notificationSettings()
            ->where('group_type', $request->groupType)
            ->update($request->only('enabled_email', 'enabled_push'));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return String
     */
    public function destroy(String $id)
    {
        $user = User::find(Auth::id());
        $notification = $user->notifications->find($id);
        $notification->delete();
        return 'Notification deleted';
    }
}
