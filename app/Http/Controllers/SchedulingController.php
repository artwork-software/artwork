<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Checklist;
use App\Models\Event;
use App\Models\GlobalNotification;
use App\Models\Project;
use App\Models\Room;
use App\Models\Scheduling;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use stdClass;

class SchedulingController extends Controller
{

    protected ?NotificationController $notificationController = null;
    protected ?stdClass $notificationData = null;

    public function __construct()
    {
        $this->notificationController = new NotificationController();
        $this->notificationData = new stdClass();
        $this->notificationData->project = new stdClass();
        $this->notificationData->task = new stdClass();
        $this->notificationData->room = new stdClass();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
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

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Scheduling $scheduling
     * @return Response
     */
    public function show(Scheduling $scheduling)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Scheduling $scheduling
     * @return Response
     */
    public function edit(Scheduling $scheduling)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Scheduling $scheduling
     * @return Response
     */
    public function update(Request $request, Scheduling $scheduling)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Scheduling $scheduling
     * @return Response
     */
    public function destroy(Scheduling $scheduling)
    {
        $scheduling->delete();
    }

    /**
     * @throws \Exception
     */
    public function sendDeadlineNotification()
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
                    $deadline = new DateTime($privateChecklistTask->deadline);
                    if ($deadline <= Carbon::now()->addDay() && $deadline >= Carbon::now()) {
                        $this->notificationData->title = 'Deadline von ' . $privateChecklistTask->name . ' ist morgen erreicht';
                        $this->notificationData->task = $privateChecklistTask;
                        $broadcastMessage = [
                            'id' => rand(1, 1000000),
                            'type' => 'error',
                            'message' => $this->notificationData->title
                        ];
                        $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
                    }
                    if ($deadline <= now()) {
                        $this->notificationData->title = $privateChecklistTask->name . ' hat ihre Deadline überschritten';
                        $this->notificationData->task = $privateChecklistTask;
                        $broadcastMessage = [
                            'id' => rand(1, 1000000),
                            'type' => 'error',
                            'message' => $this->notificationData->title
                        ];
                        $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
                    }
                }
                continue;
            }
            $tasks = $checklist->tasks()->get();
            foreach ($tasks->where('done_at', null) as $task) {
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
                    $this->notificationData->title = $taskDeadline['title'] . ' hat die Deadline überschritten';
                    $this->notificationData->task = $task;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $this->notificationData->title
                    ];
                    $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
                }
                if ($taskDeadline['type'] === 'DEADLINE_NOT_REACHED') {
                    $this->notificationData->title = 'Deadline von ' . $task->name . ' ist morgen erreicht';
                    $this->notificationData->task = $task;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $this->notificationData->title
                    ];
                    $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
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
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_NEW_TASK;
                    $this->notificationData->title = $schedule->count . ' neue Aufgaben für dich';
                    $this->notificationData->created_by = null;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $this->notificationData->title
                    ];
                    break;
                case 'PROJECT_CHANGES':
                    $project = Project::find($schedule->model_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PROJECT;
                    $this->notificationData->title = 'Es gab Änderungen an ' . $project->name;
                    $this->notificationData->project->id = $project->id;
                    $this->notificationData->project->title = $project->name;
                    $this->notificationData->created_by = null;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $this->notificationData->title
                    ];
                    break;
                case 'TASK_CHANGES':
                    $task = Task::find($schedule->model_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TASK_CHANGED;
                    $this->notificationData->title = 'Änderungen an ' . @$task->name;
                    $this->notificationData->task->title = @$task->name;
                    $this->notificationData->task->deadline = @$task->deadline;
                    $this->notificationData->created_by = null;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $this->notificationData->title
                    ];
                    break;
                case 'ROOM_CHANGES':
                    $room = Room::find($schedule->model_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_ROOM_CHANGED;
                    $this->notificationData->title = 'Änderungen an ' . @$room->name;
                    $this->notificationData->room = $room;
                    $this->notificationData->created_by = null;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $this->notificationData->title
                    ];
                    break;
                case 'EVENT_CHANGES':
                    $event = Event::find($schedule->model_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT_CHANGED;
                    $this->notificationData->title = 'Termin geändert';
                    $this->notificationData->event = $event;
                    $this->notificationData->created_by = null;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $this->notificationData->title
                    ];
                    break;
                case 'PUBLIC_CHANGES':
                    $project = Project::find($schedule->model_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT;
                    $this->notificationData->title = 'Es gab öffentlichkeitsarbeitsrelevante Änderungen an ' . $project->name;
                    $this->notificationData->project->id = $project->id;
                    $this->notificationData->project->title = $project->name;
                    $this->notificationData->created_by = null;
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $this->notificationData->title
                    ];
                    break;
            }
            $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
            $schedule->delete();
        }
    }

    /**
     * Deletes notifications that were archived 7 or more days ago
     * @return void
     */
    public function deleteOldNotifications() {
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

    public function deleteExpiredNotificationForAll(){
        $notificationForAll = GlobalNotification::all();
        foreach ($notificationForAll as $notification){
            if ($notification->expiration_date <= now()){
                $notification->delete();
            }
        }
    }
}
