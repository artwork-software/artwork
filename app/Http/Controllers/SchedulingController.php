<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Project;
use App\Models\Scheduling;
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
    public function create($userId, $type, $project = null, $task = null): Response|bool
    {
        $task = Scheduling::where('user_id', $userId)
            ->where('type', $type)
            ->where('project_id', $project)
            ->where('task_id', $task)
            ->first();

        if(!empty($task)){
            $task->increment('count', 1);
        } else {
            Scheduling::create([
                'count' => 1,
                'user_id' => $userId,
                'type' => $type,
                'project_id' => $project,
                'task_id' => $task
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
     * @param Scheduling $taskScheduling
     * @return Response
     */
    public function show(Scheduling $taskScheduling)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Scheduling $taskScheduling
     * @return Response
     */
    public function edit(Scheduling $taskScheduling)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Scheduling $taskScheduling
     * @return Response
     */
    public function update(Request $request, Scheduling $taskScheduling)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Scheduling $taskScheduling
     * @return Response
     */
    public function destroy(Scheduling $taskScheduling)
    {
        $taskScheduling->delete();
    }

    public function sendNotification(): void
    {
        $taskToNotify = Scheduling::whereDate('updated_at', '>=', Carbon::now()->addMinutes(30)->setTimezone(config('app.timezone')))->get();
        foreach ($taskToNotify as $task){
            if($task->type == 'TASK'){
                $user = User::find($task->user_id);
                $this->notificationData->type = NotificationConstEnum::NOTIFICATION_SIMPLE;
                $this->notificationData->title = $task->count . ' neue Aufgaben für dich';
                $this->notificationData->created_by = null;
                $this->notificationController->create($user, $this->notificationData);
                $task->delete();
            } else if($task->type == 'PROJECT'){
                $project = Project::find($task->project_id);
                $user = User::find($task->user_id);
                $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PROJECT;
                $this->notificationData->title = 'Es gab Änderungen an ' . $project->name;
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = null;
                $this->notificationController->create($user, $this->notificationData);
                $task->delete();
            }
        }
    }
}
