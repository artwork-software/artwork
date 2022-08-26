<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Area;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Room;
use App\Models\Sector;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            'projects' => Project::all()->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'number_of_participants' => $project->number_of_participants,
                'cost_center' => $project->cost_center,
                'sector' => $project->sector,
                'category' => $project->category,
                'genre' => $project->genre,
                'users' => $project->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'position' => $user->position,
                    'business' => $user->business,
                    'description' => $user->description,
                ]),
                'project_history' => $project->project_histories()->with('user')->orderByDesc('created_at')->get()->map(fn($history_entry) => [
                    'created_at' => Carbon::parse($history_entry->created_at)->diffInHours() < 24 ?
                        Carbon::parse($history_entry->created_at)->diffForHumans() :
                        Carbon::parse($history_entry->created_at)->format('d.m.Y, H:i'),
                    'user' => $history_entry->user,
                    'description' => $history_entry->description
                ]),
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'profile_photo_url' => $user->profile_photo_url,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'position' => $user->position,
                        'business' => $user->business,
                        'description' => $user->description,
                    ]),
                ]),
                'events' => $project->events
            ]),
            'users' => User::all(),
            'categories' => Category::all()->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),
            'genres' => Genre::all()->map(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),
            'sectors' => Sector::all()->map(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),
        ]);
    }

    public function search_departments_and_users(SearchRequest $request): array
    {

        $this->authorize('viewAny', Department::class);
        $this->authorize('viewAny', User::class);

        return [
            'departments' => Department::search($request->input('query'))->get(),
            'users' => User::search($request->input('query'))->get()
        ];

    }

    public function search(SearchRequest $request)
    {
        $this->authorize('viewAny', Project::class);

        return Project::search($request->input('query'))->get()->map(fn($project) => [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'number_of_participants' => $project->number_of_participants,
            'cost_center' => $project->cost_center,
            'sector' => $project->sector,
            'category' => $project->category,
            'genre' => $project->genre,
            'users' => $project->users->map(fn($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'profile_photo_url' => $user->profile_photo_url,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'position' => $user->position,
                'business' => $user->business,
                'description' => $user->description,
            ]),
            'project_history' => $project->project_histories()->with('user')->orderByDesc('created_at')->get()->map(fn($history_entry) => [
                'created_at' => Carbon::parse($history_entry->created_at)->diffInHours() < 24 ?
                    Carbon::parse($history_entry->created_at)->diffForHumans() :
                    Carbon::parse($history_entry->created_at)->format('d.m.Y, H:i'),
                'user' => $history_entry->user,
                'description' => $history_entry->description
            ]),
            'departments' => $project->departments->map(fn($department) => [
                'id' => $department->id,
                'name' => $department->name,
                'svg_name' => $department->svg_name,
                'users' => $department->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'position' => $user->position,
                    'business' => $user->business,
                    'description' => $user->description,
                ]),
            ]),
            'events' => $project->events
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
            'category_id' => $request->category_id,
            'genre_id' => $request->genre_id,
        ]);

        $project->users()->save(Auth::user(), ['is_admin' => true,'is_manager' => false]);

        $project_admins = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_admin', 1);
        })->get();
        $project_managers = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_manager', 1);
        })->get();

        $adminIds = [];
        $managerIds = [];
        foreach($project_admins as $admin){
            $adminIds[] = $admin->id;
        }
        foreach($project_managers as $manager){
            $managerIds[] = $manager->id;
        }

        if ($request->assigned_user_ids) {
            if (Auth::user()->can('update users') || Auth::user()->can("create and edit projects") || Auth::user()->can("admin projects") || in_array(Auth::user()->id, $adminIds) || in_array(Auth::user()->id, $managerIds)) {
                $project->users()->sync(
                    collect($request->assigned_user_ids)
                        ->map(function ($user) {
                            return $user;
                        })
                );
            } else {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        $project->departments()->sync(
            collect($request->assigned_departments)
                ->map(function ($department) {

                    $this->authorize('update', Department::find($department['id']));

                    return $department['id'];
                })
        );

        $project->project_histories()->create([
            "user_id" => Auth::id(),
            "description" => "Projekt angelegt"
        ]);

        return Redirect::route('projects', $project)->with('success', 'Project created.');
    }

    private function get_events_of_day($date_of_day, $events, $project_id = null): array
    {

        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        if (!$project_id) {
            foreach ($events as $event) {
                if (in_array($today, $event->days_of_event)) {
                    $eventsToday[] = [
                        'id' => $event->id,
                        'name' => $event->name,
                        'description' => $event->description,
                        "start_time" => $event->start_time,
                        "start_time_dt_local" => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                        "end_time" => $event->end_time,
                        "end_time_dt_local" => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                        "occupancy_option" => $event->occupancy_option,
                        "audience" => $event->audience,
                        "is_loud" => $event->is_loud,
                        "event_type_id" => $event->event_type_id,
                        "room_id" => $event->room_id,
                        "user_id" => $event->user_id,
                        "project_id" => $event->project_id,
                        "created_at" => $event->created_at,
                        "updated_at" => $event->updated_at,
                    ];
                }
            }
        } else {
            foreach ($events as $event) {
                if (in_array($today, $event->days_of_event) && $project_id === $event->project_id) {
                    $eventsToday[] = [
                        'id' => $event->id,
                        'name' => $event->name,
                        'description' => $event->description,
                        "start_time" => $event->start_time,
                        "start_time_dt_local" => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                        "end_time" => $event->end_time,
                        "end_time_dt_local" => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                        "occupancy_option" => $event->occupancy_option,
                        "audience" => $event->audience,
                        "is_loud" => $event->is_loud,
                        "event_type_id" => $event->event_type_id,
                        "room_id" => $event->room_id,
                        "user_id" => $event->user_id,
                        "project_id" => $event->project_id,
                        "created_at" => $event->created_at,
                        "updated_at" => $event->updated_at,
                    ];
                }
            }
        }


        return $eventsToday;
    }

    private function get_events_for_day_view($date_of_day, $events, $project_id = null): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        $lastEvent = null;

        if (!$project_id) {


            foreach ($events as $event) {
                if (in_array($today, $event->days_of_event)) {

                    $conflicts = [];

                    if (!blank($lastEvent)) {

                        $this_event_start_time = Carbon::parse($event['start_time']);
                        $last_event_end_time = Carbon::parse($lastEvent['end_time']);

                        if ($last_event_end_time->greaterThanOrEqualTo($this_event_start_time)) {
                            $conflicts[] = $lastEvent['id'];
                        }

                    }

                    if (Carbon::parse($event->start_time) < Carbon::parse($date_of_day)->startOfDay()->subHours(2)) {
                        $minutes_from_start = 1;
                    } else if (Carbon::parse($date_of_day)->startOfDay()->subHours(2)->diffInMinutes(Carbon::parse($event->start_time)) < 1440) {
                        $minutes_from_start = Carbon::parse($date_of_day)->startOfDay()->subHours(2)->diffInMinutes(Carbon::parse($event->start_time));
                    } else {
                        $minutes_from_start = 1;
                    }

                    $eventsToday[] = [
                        'id' => $event->id,
                        'conflicts' => $conflicts,
                        'name' => $event->name,
                        'description' => $event->description,
                        "start_time" => $event->start_time,
                        "start_time_dt_local" => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                        "end_time" => $event->end_time,
                        "end_time_dt_local" => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                        "occupancy_option" => $event->occupancy_option,
                        "minutes_from_day_start" => $minutes_from_start,
                        "duration_in_minutes" => Carbon::parse($event->start_time) < Carbon::parse($date_of_day)->startOfDay()->subHours(2) ? Carbon::parse($date_of_day)->startOfDay()->subHours(2)->diffInMinutes(Carbon::parse($event->end_time)) : Carbon::parse($event->start_time)->diffInMinutes(Carbon::parse($event->end_time)),
                        "audience" => $event->audience,
                        "is_loud" => $event->is_loud,
                        "event_type_id" => $event->event_type_id,
                        "event_type" => $event->event_type,
                        "room_id" => $event->room_id,
                        "user_id" => $event->user_id,
                        "project_id" => $event->project_id,
                        "created_at" => $event->created_at,
                        "updated_at" => $event->updated_at,
                    ];

                    $lastEvent = $event;
                }
            }
        } else {
            foreach ($events as $event) {
                if (in_array($today, $event->days_of_event) && $project_id === $event->project_id) {

                    $conflicts = [];

                    if (!blank($lastEvent)) {

                        $this_event_start_time = Carbon::parse($event['start_time']);
                        $last_event_end_time = Carbon::parse($lastEvent['end_time']);

                        if ($last_event_end_time->greaterThanOrEqualTo($this_event_start_time)) {
                            $conflicts[] = $lastEvent['id'];
                        }

                    }

                    $eventsToday[] = [
                        'id' => $event->id,
                        'conflicts' => $conflicts,
                        'name' => $event->name,
                        'description' => $event->description,
                        "start_time" => $event->start_time,
                        "start_time_dt_local" => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                        "end_time" => $event->end_time,
                        "end_time_dt_local" => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                        "occupancy_option" => $event->occupancy_option,
                        "minutes_from_day_start" => Carbon::parse($date_of_day)->startOfDay()->subHours(2)->diffInMinutes(Carbon::parse($event->start_time)),
                        "duration_in_minutes" => Carbon::parse($event->start_time)->diffInMinutes(Carbon::parse($event->end_time)),
                        "audience" => $event->audience,
                        "is_loud" => $event->is_loud,
                        "event_type_id" => $event->event_type_id,
                        "event_type" => $event->event_type,
                        "room_id" => $event->room_id,
                        "user_id" => $event->user_id,
                        "project_id" => $event->project_id,
                        "created_at" => $event->created_at,
                        "updated_at" => $event->updated_at,
                    ];

                    $lastEvent = $event;
                }
            }
        }

        return $eventsToday;
    }

    public function get_conflicts_in_room_for_start_time(Room $room): array
    {

        $start_time = request('start_time');

        $conflicting_events = [];

        foreach ($room->events as $event) {

            if (Carbon::parse($start_time)->between(Carbon::parse($event->start_time), Carbon::parse($event->end_time))) {

                $conflicting_events[] = [
                    'event_name' => $event->name,
                    'event_type' => $event->event_type,
                    'project' => $event->project
                ];

            }

        }

        return $conflicting_events;

    }

    public function get_conflicts_in_room_for_end_time(Room $room): array
    {
        $end_time = request('end_time');

        $conflicting_events = [];

        foreach ($room->events as $event) {

            if (Carbon::parse($end_time)->between(Carbon::parse($event->start_time), Carbon::parse($event->end_time))) {

                $conflicting_events[] = [
                    'event_name' => $event->name,
                    'event_type' => $event->event_type,
                    'project' => $event->project
                ];

            }

        }

        return $conflicting_events;

    }

    private function conflict_event_ids($events_with_ascending_end_time): array
    {

        $conflict_event_ids = array();

        $lastEvent = null;

        foreach ($events_with_ascending_end_time as $event) {

            if (!blank($lastEvent)) {

                $this_event_start_time = Carbon::parse($event['start_time']);
                $last_event_end_time = Carbon::parse($lastEvent['end_time']);

                if ($last_event_end_time->greaterThanOrEqualTo($this_event_start_time)) {
                    $conflict_event_ids[] = [$lastEvent['id'], $event['id']];
                }

            }

            $lastEvent = $event;
        }


        return $conflict_event_ids;
    }

    /**
     * Display the specified resource.
     *
     * @param Project $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Project $project, Request $request)
    {

        $public_checklists = Checklist::where('project_id', $project->id)->where('user_id', null)->get();
        $private_checklists = $project->checklists()->where('user_id', Auth::id())->get();

        $project_admins = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_admin', 1);
        })->get();
        $project_managers = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_manager', 1);
        })->get();

        $events = [];
        if ($request->query('calendarType') === 'monthly') {
            $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));
        }
        $openTab = $request->openTab;
        if(!$openTab){
            $openTab = 'checklist';
        }

        $hours = ['0:00', '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];
        $wanted_day = Carbon::parse($request->query('wanted_day'));

        $eventsWithoutRoom = Event::whereNull('room_id')->get();
        $eventsWithoutRoomCount = Event::whereNull('room_id')
            ->whereDate('start_time', '<=', Carbon::parse($request->query('month_end')))
            ->whereDate('end_time', '>=', Carbon::parse($request->query('month_start')))->count();

        return inertia('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'number_of_participants' => $project->number_of_participants,
                'cost_center' => $project->cost_center,
                'sector' => $project->sector,
                'category' => $project->category,
                'genre' => $project->genre,
                'project_admins' => $project_admins,
                'project_managers' => $project_managers,
                'rooms' => Room::whereHas('events', function ($query) use ($project) {
                    $query->where('project_id', $project->id)->orderBy('end_time', 'ASC')->with('event_type');
                })->get()->map(fn($room) => $request->query('calendarType') === 'monthly' ? [
                    'id' => $room->id,
                    'name' => $room->name,
                    'area_id' => $room->area_id,
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ]),
                    'days_in_month' => collect($period)->map(function ($date_of_day) use ($project, $room) {
                        $events = $this->get_events_of_day($date_of_day, $room->events, $project->id);

                        $conflicts = $this->conflict_event_ids($events);

                        return [
                            'date_local' => $date_of_day->toDateTimeLocalString(),
                            'date' => $date_of_day->format('d.m.Y'),
                            'conflicts' => $conflicts,
                            'events' => $events
                        ];
                    }),
                ] : [
                    'id' => $room->id,
                    'name' => $room->name,
                    'area_id' => $room->area_id,
                    'events' => $this->get_events_for_day_view($wanted_day, $room->events, $project->id)
                ]),
                'events' => $project->events->map(fn($event) => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'description' => $event->description,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'occupancy_option' => $event->occupancy_option,
                    'audience' => $event->audience,
                    'is_loud' => $event->is_loud,
                    'event_type_id' => $event->event_type_id,
                    'room_id' => $event->room_id,
                    'project_id' => $event->project_id
                ]),
                'users' => $project->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'position' => $user->position,
                    'business' => $user->business,
                    'description' => $user->description,
                ]),
                'project_history' => $project->project_histories()->with('user')->orderByDesc('created_at')->get()->map(fn($history_entry) => [
                    'created_at' => Carbon::parse($history_entry->created_at)->diffInHours() < 24 ?
                        Carbon::parse($history_entry->created_at)->diffForHumans() :
                        Carbon::parse($history_entry->created_at)->format('d.m.Y, H:i'),
                    'user' => $history_entry->user,
                    'description' => $history_entry->description
                ]),
                'project_files' => $project->project_files,
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'profile_photo_url' => $user->profile_photo_url,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'position' => $user->position,
                        'business' => $user->business,
                        'description' => $user->description,
                    ]),
                ]),

                'isMemberOfADepartment' => $project->departments->contains(fn($department) => $department->users->contains(Auth::user())),
                'public_checklists' => $public_checklists->map(fn($checklist) => [
                    'id' => $checklist->id,
                    'name' => $checklist->name,
                    //determines if the checklist is already opened by default
                    'showContent' => true,
                    'tasks' => $checklist->tasks()->orderBy('order')->get()->map(fn($task) => [
                        'id' => $task->id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'deadline' => $task->deadline === null ? null : Carbon::parse($task->deadline)->format('d.m.Y, H:i'),
                        'deadline_dt_local' => $task->deadline === null ? null : Carbon::parse($task->deadline)->toDateTimeLocalString(),
                        'order' => $task->order,
                        'done' => $task->done,
                        'done_by_user' => $task->user_who_done,
                        'done_at' => Carbon::parse($task->done_at)->format('d.m.Y, H:i'),
                        'done_at_dt_local' => Carbon::parse($task->done_at)->toDateTimeLocalString()
                    ]),
                    'departments' => $checklist->departments->map(fn($department) => [
                        'id' => $department->id,
                        'name' => $department->name,
                        'svg_name' => $department->svg_name,
                    ])
                ]),
                'private_checklists' => $private_checklists->map(fn($checklist) => [
                    'id' => $checklist->id,
                    'name' => $checklist->name,
                    //determines if the checklist is already opened by default
                    'showContent' => true,
                    'tasks' => $checklist->tasks()->orderBy('order')->get()->map(fn($task) => [
                        'id' => $task->id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'deadline' => $task->deadline === null ? null : Carbon::parse($task->deadline)->format('d.m.Y, H:i'),
                        'deadline_dt_local' => $task->deadline === null ? null : Carbon::parse($task->deadline)->toDateTimeLocalString(),
                        'order' => $task->order,
                        'done' => $task->done,
                        'done_by_user' => $task->user_who_done,
                        'done_at' => Carbon::parse($task->done_at)->format('d.m.Y, H:i'),
                        'done_at_dt_local' => Carbon::parse($task->done_at)->toDateTimeLocalString()
                    ])
                ]),
                'comments' => $project->comments->map(fn($comment) => [
                    'id' => $comment->id,
                    'text' => $comment->text,
                    'created_at' => $comment->created_at->format('d.m.Y, H:i'),
                    'user' => $comment->user
                ])
            ],
            'categories' => Category::all()->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),
            'genres' => Genre::all()->map(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),
            'sectors' => Sector::all()->map(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),
            'event_types' => EventType::all()->map(fn($event_type) => [
                'id' => $event_type->id,
                'name' => $event_type->name,
                'svg_name' => $event_type->svg_name,
                'project_mandatory' => $event_type->project_mandatory,
                'individual_name' => $event_type->individual_name,
            ]),
            'events_without_room' => $request->query('calendarType') === 'monthly' ? [
                "count" => $eventsWithoutRoomCount,
                'days_in_month' => collect($period)->map(fn($date_of_day) => [
                    'date_local' => $date_of_day->toDateTimeLocalString(),
                    'date' => $date_of_day->format('d.m.Y'),
                    'events' => $this->get_events_of_day($date_of_day, $eventsWithoutRoom, $project->id),
                ]),
            ] : [
                "count" => $eventsWithoutRoomCount,
                'events' => $this->get_events_for_day_view($wanted_day, $eventsWithoutRoom, $project->id),
            ],
            'checklist_templates' => ChecklistTemplate::all()->map(fn($checklist_template) => [
                'id' => $checklist_template->id,
                'name' => $checklist_template->name,
                'task_templates' => $checklist_template->task_templates->map(fn($task_template) => [
                    'id' => $task_template->id,
                    'name' => $task_template->name,
                    'description' => $task_template->description
                ]),
            ]),
            'areas' => Area::all()->map(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->rooms()->with('room_admins', 'events.event_type')->orderBy('order')->get()->map(fn($room) => [
                    'conflicts_start_time' => $this->get_conflicts_in_room_for_start_time($room),
                    'conflicts_end_time' => $this->get_conflicts_in_room_for_end_time($room),
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => $room->temporary,
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                    'start_date_dt_local' => Carbon::parse($room->start_date)->toDateString(),
                    'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                    'end_date_dt_local' => Carbon::parse($room->end_date)->toDateString(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ]),
            'days_this_month' => $request->query('calendarType') === 'monthly' ? collect($period)->map(fn($date_of_day) => [
                'date_formatted' => strtoupper($date_of_day->isoFormat('dd DD.MM.')),
            ]) : [],
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $request->query('wanted_day'),
            'requested_start_time' => $request->query('month_start'),
            'requested_end_time' => $request->query('month_end'),
            'hours_of_day' => $hours,
            'shown_day_formatted' => Carbon::parse($request->query('wanted_day'))->format('l d.m.Y'),
            'shown_day_local' => Carbon::parse($request->query('wanted_day')),
            'calendarType' => $request->query('calendarType') === 'daily' ? 'daily' : 'monthly',
            'openTab'=> $openTab
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Project $project
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
                    'profile_photo_url' => $user->profile_photo_url,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'position' => $user->position,
                    'business' => $user->business,
                    'description' => $user->description,
                ]),
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'profile_photo_url' => $user->profile_photo_url,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'position' => $user->position,
                        'business' => $user->business,
                        'description' => $user->description,
                    ]),
                ]),
            ],
            'users' => User::all(),
            'departments' => Department::all()
        ]);
    }

    private function history_description_change($change): string
    {

        return match ($change) {
            'name' => 'Projektname wurde geändert',
            'description' => 'Kurzbeschreibung wurde geändert',
            'number_of_participants' => 'Anzahl Teilnehmer:innen geändert',
            'cost_center' => 'Kostenträger geändert',
            'sector_id' => 'Bereich geändert',
            'category_id' => 'Kategorie geändert',
            'genre_id' => 'Genre geändert',
        };

    }

    private function history_description_added($change): string
    {

        return match ($change) {
            'description' => 'Kurzbeschreibung wurde hinzugefügt',
            'number_of_participants' => 'Anzahl Teilnehmer:innen hinzugefügt',
            'cost_center' => 'Kostenträger hinzugefügt',
            'sector_id' => 'Bereich hinzugefügt',
            'category_id' => 'Kategorie hinzugefügt',
            'genre_id' => 'Genre hinzugefügt',
        };

    }

    private function history_description_removed($change): string
    {

        return match ($change) {
            'description' => 'Kurzbeschreibung wurde gelöscht',
            'number_of_participants' => 'Anzahl Teilnehmer:innen gelöscht',
            'cost_center' => 'Kostenträger gelöscht',
            'sector_id' => 'Bereich gelöscht',
            'category_id' => 'Kategorie gelöscht',
            'genre_id' => 'Genre gelöscht',
        };

    }

    private function add_to_history($project): void
    {

        $original = $project->getOriginal();
        $changes = $project->getDirty();

        $changed_fields = array_keys($changes);

        foreach ($changed_fields as $change) {

            if ($original[$change] === null) {
                $project->project_histories()->create([
                    "user_id" => Auth::id(),
                    "description" => $this->history_description_added($change)
                ]);
            } else if ($changes[$change] === null) {
                $project->project_histories()->create([
                    "user_id" => Auth::id(),
                    "description" => $this->history_description_removed($change)
                ]);
            } else {
                $project->project_histories()->create([
                    "user_id" => Auth::id(),
                    "description" => $this->history_description_change($change)
                ]);
            }

        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProjectRequest $request
     * @param Project $project
     * @return JsonResponse|RedirectResponse
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $update_properties = $request->only('name', 'description', 'number_of_participants', 'cost_center', 'sector_id', 'category_id', 'genre_id');

        $project_admins = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_admin', 1);
        })->get();
        $project_managers = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_manager', 1);
        })->get();

        $adminIds = [];
        $managerIds = [];
        foreach($project_admins as $admin){
            $adminIds[] = $admin->id;
        }
        foreach($project_managers as $manager){
            $managerIds[] = $manager->id;
        }

        $project->fill($update_properties);

        $this->add_to_history($project);

        $project->save();

        if (empty($request->assigned_user_ids)) {
            $project->users()->detach();
        }

        if ($request->assigned_user_ids) {
            if (Auth::user()->can('update users') || Auth::user()->can("create and edit projects") || Auth::user()->can("admin projects") || in_array(Auth::user()->id, $adminIds) || in_array(Auth::user()->id, $managerIds)) {
                $project->users()->sync(
                    collect($request->assigned_user_ids)
                        ->map(function ($user_id) {
                            return $user_id;
                        })
                );
            } else {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }


        if (Auth::user()->can('update departments') || Auth::user()->can("create and edit projects") || Auth::user()->can("admin projects") || in_array(Auth::user()->id, $adminIds) || in_array(Auth::user()->id, $managerIds)) {
            $project->departments()->sync(
                collect($request->assigned_departments)
                    ->map(function ($department) {
                        return $department['id'];
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign departments to a project.'], 403);
        }

        return Redirect::back();
    }

    /**
     * Duplicates the project whose id is passed in the request
     */
    public function duplicate(Project $project)
    {

        $new_project = Project::create([
            'name' => '(Kopie) ' . $project->name,
            'description' => $project->description,
            'number_of_participants' => $project->number_of_participants,
            'cost_center' => $project->cost_center,
            'sector_id' => $project->sector_id,
            'category_id' => $project->category_id,
            'genre_id' => $project->genre_id,
        ]);

        $project_admins = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_admin', 1);
        })->get();
        $project_managers = User::whereHas('projects', function ($q) use ($project) {
            $q->where('is_manager', 1);
        })->get();

        $adminIds = [];
        $managerIds = [];
        foreach($project_admins as $admin){
            $adminIds[] = $admin->id;
        }
        foreach($project_managers as $manager){
            $managerIds[] = $manager->id;
        }

        foreach ($project->checklists as $checklist) {
            $replicated_checklist = $checklist->replicate()->fill([
                'project_id' => $new_project->id
            ]);
            $replicated_checklist->save();

            foreach ($checklist->departments as $department) {
                $replicated_checklist->departments()->attach($department);
            }

            foreach ($checklist->tasks as $task) {
                $replicated_task = $task->replicate()->fill([
                    'checklist_id' => $replicated_checklist->id,
                    'deadline' => null,
                    'done' => false,
                    'done_at' => null
                ]);

                $replicated_task->checklist()->associate($replicated_checklist);
                $replicated_task->save();
            }
        }

        $new_project->users()->attach([Auth::id() => ['is_admin' => true]]);

        if ($project->users) {
            if (Auth::user()->can('update users') || Auth::user()->can("create and edit projects") || Auth::user()->can("admin projects") || in_array(Auth::user()->id, $adminIds) || in_array(Auth::user()->id, $managerIds)) {
                $new_project->users()->sync(
                    collect($project->users)
                        ->map(function ($user) {
                            return $user['id'];
                        })
                );
            } else {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        if ($project->departments) {
            $new_project->departments()->sync(
                collect($project->departments)
                    ->map(function ($department) {

                        $this->authorize('update', Department::find($department['id']));

                        return $department['id'];
                    })
            );
        }

        $new_project->project_histories()->create([
            "user_id" => Auth::id(),
            "description" => "Projekt angelegt"
        ]);

        $project->project_histories()->create([
            "user_id" => Auth::id(),
            "description" => "Projekt wurde dupliziert"
        ]);

        return Redirect::route('projects.show', $new_project->id)->with('success', 'Project created.');


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Project $project
     * @return RedirectResponse
     */
    public function destroy(Project $project)
    {

        $project->load('events');

        $project->events()->delete();

        $project->delete();

        return Redirect::route('projects')->with('success', 'Project moved to trash');
    }

    public function forceDelete(int $id)
    {

        $project = Project::onlyTrashed()->findOrFail($id);

        $project->forceDelete();
        $project->events()->withTrashed()->forceDelete();
        return Redirect::route('projects.trashed')->with('success', 'Room restored');
    }

    public function restore(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->restore();
        $project->events()->withTrashed()->restore();

        return Redirect::route('projects.trashed')->with('success', 'Room restored');
    }



    public function getTrashed()
    {
        return inertia('Trash/Projects', [
            'trashed_projects' => Project::onlyTrashed()->get()->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'number_of_participants' => $project->number_of_participants,
                'cost_center' => $project->cost_center,
                'sector' => $project->sector,
                'category' => $project->category,
                'genre' => $project->genre,
                'users' => $project->users->map(fn($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'position' => $user->position,
                    'business' => $user->business,
                    'description' => $user->description,
                ]),
                'project_history' => $project->project_histories()->with('user')->get()->map(fn($history_entry) => [
                    'created_at' => Carbon::parse($history_entry->created_at)->format('d.m.Y, H:i'),
                    'user' => $history_entry->user,
                    'description' => $history_entry->description
                ]),
                'departments' => $project->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => $department->users->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'profile_photo_url' => $user->profile_photo_url,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'position' => $user->position,
                        'business' => $user->business,
                        'description' => $user->description,
                    ]),
                ])
            ])
        ]);
    }
}
