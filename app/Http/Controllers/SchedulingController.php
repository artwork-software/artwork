<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Checklist;
use App\Models\Event;
use App\Models\Project;
use App\Models\Scheduling;
use App\Models\Task;
use App\Models\User;
use App\Notifications\SimpleNotification;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
     *
     * @return Response
     */
    public function create($userId, $type, $project = null, $task = null, $event = null): Response|bool
    {
        $scheduling = Scheduling::where('user_id', $userId)
            ->where('type', $type)
            ->where('project_id', $project)
            ->where('task_id', $task)
            ->where('event_id', $event)
            ->first();

        if (!empty($scheduling)) {
            $scheduling->increment('count', 1);
        } else {
            Scheduling::create([
                'count' => 1,
                'user_id' => $userId,
                'type' => $type,
                'project_id' => $project,
                'task_id' => $task,
                'event_id' => $event
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
                        $this->notificationController->create($user, $this->notificationData);
                    }
                    if ($deadline <= now()) {
                        $this->notificationData->title = $privateChecklistTask->name . ' hat ihre Deadline überschritten';
                        $this->notificationData->task = $privateChecklistTask;
                        $this->notificationController->create($user, $this->notificationData);
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
                $departments = $checklist->departments()->get();
                foreach ($departments as $department) {
                    $user_ids = $department->users()->get(['user_id']);
                    foreach ($user_ids as $user_id) {
                        $userForNotify[$task->id][$user_id->user_id] = $user_id->user_id;
                    }
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
                    $this->notificationController->create($user, $this->notificationData);
                }
                if ($taskDeadline['type'] === 'DEADLINE_NOT_REACHED') {
                    $this->notificationData->title = 'Deadline von ' . $task->name . ' ist morgen erreicht';
                    $this->notificationData->task = $task;
                    $this->notificationController->create($user, $this->notificationData);
                }
            }
        }
    }

    public function sendNotification(): void
    {
        $scheduleToNotify = Scheduling::where('updated_at', '<=', Carbon::now()->addMinutes(30)->setTimezone(config('app.timezone')))->get();
        foreach ($scheduleToNotify as $schedule) {
            $user = User::find($schedule->user_id);
            switch ($schedule->type) {
                case 'TASK':
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_NEW_TASK;
                    $this->notificationData->title = $schedule->count . ' neue Aufgaben für dich';
                    $this->notificationData->created_by = null;
                    break;
                case 'PROJECT':
                    $project = Project::find($schedule->project_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PROJECT;
                    $this->notificationData->title = 'Es gab Änderungen an ' . $project->name;
                    $this->notificationData->project->id = $project->id;
                    $this->notificationData->project->title = $project->name;
                    $this->notificationData->created_by = null;
                    break;
                case 'TASK_CHANGES':
                    $task = Task::find($schedule->task_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TASK_CHANGED;
                    $this->notificationData->title = 'Änderungen an ' . $task->name;
                    $this->notificationData->task->title = $task->name;
                    $this->notificationData->task->deadline = $task->deadline;
                    $this->notificationData->created_by = null;
                    break;
                case 'EVENT':
                    $event = Event::find($schedule->event_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT_CHANGED;
                    $this->notificationData->title = 'Termin geändert';
                    $this->notificationData->event = $event;
                    $this->notificationData->created_by = null;
                    break;
            }
            $this->notificationController->create($user, $this->notificationData);
            $schedule->delete();
        }
    }
}
