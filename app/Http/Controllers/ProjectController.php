<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Project::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('Projects/ProjectManagement', [
            'projects' => Project::paginate(10)->through(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'number_of_participants' => $project->number_of_participants,
                'cost_center' => $project->cost_center,
                'sector_id' => $project->sector_id,
                'category_id' => $project->sector_id,
                'genre_id' => $project->genre_id,
                'users' => $project->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url
                ]),
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'profile_photo_url' => $user->profile_photo_url
                    ]),
                ])
            ]),
            'users' => User::all(),
            'categories' => Category::paginate(10)->through(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'svg_name' => $category->svg_name,
                'projects' => $category->projects
            ]),
            'genres' => Genre::paginate(10)->through(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),
            'sectors' => Sector::paginate(10)->through(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'number_of_participants' => $request->number_of_participants,
            'cost_center' => $request->cost_center,
            'sector_id' => $request->sector_id,
            'category_id' => $request->sector_id,
            'genre_id' => $request->genre_id,
        ]);

        if (Auth::user()->can('update users')) {
            $project->users()->sync(
                collect($request->assigned_user_ids)
                    ->map(function ($user_id) {
                        return $user_id;
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $project->departments()->sync(
            collect($request->assigned_departments)
                ->map(function ($department) {

                    $this->authorize('update', Department::find($department['id']));

                    return $department['id'];
                })
        );

        return Redirect::route('projects')->with('success', 'Project created.');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Project $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Project $project)
    {
        return inertia('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'number_of_participants' => $project->number_of_participants,
                'cost_center' => $project->cost_center,
                'sector_id' => $project->sector_id,
                'category_id' => $project->sector_id,
                'genre_id' => $project->genre_id,
                'users' => $project->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url
                ]),
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'profile_photo_url' => $user->profile_photo_url
                    ]),
                ]),
                'checklists' => $project->checklists->map(fn($checklist) => [
                    'id' => $checklist->id,
                    'name' => $checklist->name,
                    'tasks' => $checklist->tasks->map(fn($task) => [
                        'id' => $task->id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'deadline' => $task->deadline,
                        'done' => $task->done,
                    ]),
                    'users' => $checklist->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'profile_photo_url' => $user->profile_photo_url
                    ])
                ]),
                'comments' => $project->comments->map(fn($comment) => [
                    'id' => $comment->id,
                    'text' => $comment->text,
                    'created' => $comment->created
                ])
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Project $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Project $project)
    {
        return inertia('Projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'number_of_participants' => $project->number_of_participants,
                'cost_center' => $project->cost_center,
                'sector_id' => $project->sector_id,
                'category_id' => $project->sector_id,
                'genre_id' => $project->genre_id,
                'users' => $project->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url
                ]),
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'profile_photo_url' => $user->profile_photo_url
                    ]),
                ]),
            ],
            'users' => User::all(),
            'departments' => Department::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Project $project
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->only('name', 'description', 'number_of_participants', 'cost_center', 'sector_id', 'category_id', 'genre_id'));

        if (Auth::user()->can('update users')) {
            $project->users()->sync(
                collect($request->assigned_user_ids)
                    ->map(function ($user_id) {
                        return $user_id;
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        if (Auth::user()->can('update departments')) {
            $project->departments()->sync(
                collect($request->assigned_departments)
                    ->map(function ($department) {
                        return $department['id'];
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign departments to a project.'], 403);
        }

        return Redirect::route('projects.update', $project -> id)->with('success', 'Project updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return Redirect::back()->with('success', 'Project deleted');
    }
}
