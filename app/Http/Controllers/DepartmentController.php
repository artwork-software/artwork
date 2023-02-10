<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\PermissionNameEnum;
use App\Events\DepartmentUpdated;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Resources\DepartmentIndexResource;
use App\Http\Resources\DepartmentShowResource;
use App\Http\Resources\UserIndexResource;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class DepartmentController extends Controller
{
    protected ?NotificationController $notificationController = null;
    protected ?\stdClass $notificationData = null;


    public function __construct()
    {
        $this->authorizeResource(Department::class);

        // init notification system
        $this->notificationController = new NotificationController();
        $this->notificationData = new \stdClass();
        $this->notificationData->team = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TEAM;
    }

    public function search(SearchRequest $request)
    {
        if(!Auth::user()->can(PermissionNameEnum::PROJECT_UPDATE->value)){
            return false;
        }

        return Department::search($request->input('query'))->get()->map(fn ($department) => [
            'id' => $department->id,
            'name' => $department->name,
            'svg_name' => $department->svg_name,
            'users' => UserIndexResource::collection($department->users)->resolve()
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index()
    {
        return inertia('Departments/DepartmentManagement', [
            'departments' => DepartmentIndexResource::collection(Department::all())->resolve(),
            'users' => User::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response|ResponseFactory
     */
    public function create()
    {
        return inertia('Departments/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreDepartmentRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create([
            'name' => $request->name,
            'svg_name' => $request->svg_name
        ]);

        $department->users()->sync(
            collect($request->assigned_users)
                ->map(function ($user) {
                    $this->authorize('update', User::find($user['id']));
                    return $user['id'];
                })
        );

        // create and send notification data
        $this->notificationData->title = 'Du wurdest zu Team ' . $department->name . ' hinzugefügt';
        $this->notificationData->team = $department;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();
        $broadcastMesssage = [
            'id' => rand(10, 1000000),
            'type' => 'success',
            'message' => $this->notificationData->title
        ];
        $this->notificationController->create($department->users->all(), $this->notificationData, $broadcastMesssage);

        broadcast(new DepartmentUpdated())->toOthers();

        return Redirect::route('departments')->with('success', 'Department created.');
    }

    /**
     * Show the specified resource.
     *
     * @param  Department  $department
     * @return Response|ResponseFactory
     */
    public function show(Department $department)
    {
        return inertia('Departments/Show', [
            'department' => new DepartmentShowResource($department)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Department  $department
     * @return Response|ResponseFactory
     */
    public function edit(Department $department)
    {
        return inertia('Departments/Edit', [
            'department' => new DepartmentShowResource($department),
            'users' => User::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Department  $department
     * @return RedirectResponse
     */
    public function update(Request $request, Department $department)
    {
        // get team member before update
        $teamIdsBefore = [];
        $teamMemberBefore = $department->users()->get();
        foreach ($teamMemberBefore as $memberBefore){
            $teamIdsBefore[] = $memberBefore->id;
        }

        $department->update($request->only('name', 'svg_name'));

        $department->users()->sync(
            collect($request->users)
                ->map(function ($user) {
                    $this->authorize('update', User::find($user['id']));

                    return $user['id'];
                })
        );

        // get team member after update
        $teamIdsAfter = [];
        $teamMemberAfter = $department->users()->get();

        foreach ($teamMemberAfter as $memberAfter) {
            $teamIdsAfter[] = $memberAfter->id;
            // send notification to new team member
            if(!in_array($memberAfter->id, $teamIdsBefore)){
                $this->notificationData->title = 'Du wurdest zu Team "' . $department->name . '" hinzugefügt';
                $this->notificationData->team = $department;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMesssage = [
                    'id' => rand(10, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($memberAfter, $this->notificationData, $broadcastMesssage);
            }
        }

        foreach ($teamIdsBefore as $teamMemberBefore){
            // send notification to removed team member
            if(!in_array($teamMemberBefore, $teamIdsAfter)){
                $user = User::find($teamMemberBefore);
                $this->notificationData->title = 'Du wurdest aus Team "' . $department->name . '" gelöscht';
                $this->notificationData->team = $department;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMesssage = [
                    'id' => rand(10, 1000000),
                    'type' => 'error',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($user, $this->notificationData, $broadcastMesssage);
            }
        }

        broadcast(new DepartmentUpdated())->toOthers();

        //return back();
        return Redirect::route('departments', $department->id)->with('success', 'Department updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Department  $department
     * @return RedirectResponse
     */
    public function destroy(Department $department)
    {
        // create and send notification data
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TEAM;
        $this->notificationData->title = 'Team "' . $department->name . '" wurde gelöscht';
        $this->notificationData->team = $department;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();
        $broadcastMesssage = [
            'id' => rand(10, 1000000),
            'type' => 'error',
            'message' => $this->notificationData->title
        ];
        $this->notificationController->create($department->users->all(), $this->notificationData, $broadcastMesssage);

        $department->delete();

        broadcast(new DepartmentUpdated())->toOthers();

        return Redirect::route('departments')->with('success', 'Department deleted');
    }
}
