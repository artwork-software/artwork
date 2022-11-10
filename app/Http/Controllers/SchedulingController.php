<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Event;
use App\Models\Project;
use App\Models\Scheduling;
use App\Models\Task;
use App\Models\User;
use App\Notifications\SimpleNotification;
use Carbon\Carbon;
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

        if(!empty($scheduling)){
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

    public function sendDeadlineNotification(){

    }

    public function sendNotification(): void
    {
        $scheduleToNotify = Scheduling::whereDate('updated_at', '>=', Carbon::now()->addMinutes(30)->setTimezone(config('app.timezone')))->get();
        foreach ($scheduleToNotify as $schedule){
            $user = User::find($schedule->user_id);

            switch ($schedule->type){
                case 'TASK':
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_SIMPLE;
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
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_SIMPLE;
                    $this->notificationData->title = 'Änderungen an ' . $task->name;
                    $this->notificationData->created_by = null;
                    break;
                case 'EVENT':
                    $event = Event::find($schedule->event_id);
                    $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT;
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
