<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class DepartmentController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Department::class);
    }

    public function search(SearchRequest $request)
    {
        $this->authorize('viewAny', Department::class);

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
        $department->update($request->only('name', 'svg_name'));

        $department->users()->sync(
            collect($request->users)
                ->map(function ($user) {
                    $this->authorize('update', User::find($user['id']));

                    return $user['id'];
                })
        );

        broadcast(new DepartmentUpdated())->toOthers();

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
        $department->delete();

        broadcast(new DepartmentUpdated())->toOthers();

        return Redirect::route('departments')->with('success', 'Department deleted');
    }
}
