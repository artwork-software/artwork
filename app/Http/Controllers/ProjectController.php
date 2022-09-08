<?php

namespace App\Http\Controllers;

use App\Enums\CalendarTimeEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\EventCollectionForDailyCalendarResource;
use App\Http\Resources\EventCollectionForMonthlyCalendarResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectEditResource;
use App\Http\Resources\ProjectIndexResource;
use App\Http\Resources\ProjectShowResource;
use App\Http\Resources\RoomIndexResource;
use App\Models\Area;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

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
        $projects = Project::query()
            ->with([
                'project_histories.user',
                'sector',
                'category',
                'genre',
                'users.departments',
                'departments.users.departments',
                'events'
            ])
            ->get();

        return inertia('Projects/ProjectManagement', [
            'projects' => ProjectIndexResource::collection($projects)->resolve(),

            'users' => User::all(),

            'categories' => Category::query()->with('projects')->get()->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),

            'genres' => Genre::query()->with('projects')->get()->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),

            'sectors' => Sector::query()->with('projects')->get()->map(fn ($sector) => [
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
        $projects = Project::search($request->input('query'))->get();

        return ProjectIndexResource::collection($projects)->resolve();
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
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(StoreProjectRequest $request)
    {
        if (! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects'])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        if (! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects'])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $departments = collect($request->assigned_departments)
            ->map(fn ($department) => Department::query()->findOrFail($department['id']))
            ->map(fn (Department $department) => $this->authorize('update', $department));

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'number_of_participants' => $request->number_of_participants,
            'cost_center' => $request->cost_center,
            'sector_id' => $request->sector_id,
            'category_id' => $request->category_id,
            'genre_id' => $request->genre_id,
        ]);

        $project->users()->save(Auth::user(), ['is_admin' => true, 'is_manager' => false]);

        if ($request->assigned_user_ids) {
            $project->users()->sync(collect($request->assigned_user_ids));
        }

        $project->departments()->sync($departments->pluck('id'));

        $project->project_histories()->create([
            'user_id' => Auth::id(),
            'description' => 'Projekt angelegt'
        ]);

        return Redirect::route('projects', $project)->with('success', 'Project created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  Project  $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Project $project, Request $request)
    {
        $calendarType = $request->query('calendarType');

        if ($calendarType === CalendarTimeEnum::MONTHLY) {
            $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));
        }

        $areas = Area::query()
            ->with([
                'rooms.room_admins',
                'rooms.events.creator',
                'rooms.creator',
                'rooms.events.event_type',
                'rooms.events.room'
            ])
            ->get();

        $eventsWithoutRoom = Event::query()
            ->with('sameRoomEvents')
            ->whereOccursBetween(Carbon::parse($request->query('month_start')), Carbon::parse($request->query('month_end')))
            ->whereNull('room_id')
            ->where('project_id', $project->id)
            ->get();

        $project->load(['events.sameRoomEvents', 'events.creator', 'comments.user']);

        return inertia('Projects/Show', [
            'project' => new ProjectShowResource($project),

            'categories' => Category::query()->with('projects')->get()->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),

            'genres' => Genre::query()->with('projects')->get()->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),

            'sectors' => Sector::query()->with('projects')->get()->map(fn ($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),

            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),

            'events_without_room' => $calendarType === CalendarTimeEnum::MONTHLY
                ? new EventCollectionForMonthlyCalendarResource($eventsWithoutRoom)
                : new EventCollectionForDailyCalendarResource($eventsWithoutRoom),

            'checklist_templates' => ChecklistTemplate::all()->map(fn ($checklist_template) => [
                'id' => $checklist_template->id,
                'name' => $checklist_template->name,
                'task_templates' => $checklist_template->task_templates->map(fn ($task_template) => [
                    'id' => $task_template->id,
                    'name' => $task_template->name,
                    'description' => $task_template->description
                ]),
            ]),

            'areas' => $areas->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexResource::collection($area->rooms->sortBy('order'))->resolve(),
            ]),

            'days_this_month' => $calendarType === CalendarTimeEnum::MONTHLY
                ? $period->map(fn (Carbon $date) => ['date_formatted' => Str::upper($date->isoFormat('dd DD.MM.'))])
                : [],

            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $request->query('wanted_day'),
            'requested_start_time' => $request->query('month_start'),
            'requested_end_time' => $request->query('month_end'),
            'hours_of_day' => config('calendar.hours'),
            'shown_day_formatted' => Carbon::parse($request->query('wanted_day'))->format('l d.m.Y'),
            'shown_day_local' => Carbon::parse($request->query('wanted_day')),
            'calendarType' => $calendarType === CalendarTimeEnum::DAILY ?: CalendarTimeEnum::MONTHLY,
            'openTab' => $request->openTab ?: 'checklist',
            'project_id' => $project->id,
            'opened_checklists' => User::where('id', Auth::id())->first()->opened_checklists,
            'first_start' => Carbon::parse($this->get_first_start_time($project->events))->format('d.m.Y'),
            'last_end' => Carbon::parse($this->get_last_end_time($project->events))->format('d.m.Y'),
        ]);
    }

    private function get_first_start_time(mixed $events)
    {
        $first_start = null;
        foreach ($events as $event) {
            if ($first_start !== null) {
                if ($event->start_time < $first_start) {
                    $first_start = $event->start_time;
                }
            } else {
                $first_start = $event->start_time;
            }
        }

        return $first_start;
    }

    private function get_last_end_time(mixed $events)
    {
        $last_end = null;
        foreach ($events as $event) {
            if ($event->end_time > $last_end) {
                $last_end = $event->end_time;
            }
        }

        return $last_end;
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  Project  $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Project $project)
    {
        return inertia('Projects/Edit', [
            'project' => new ProjectEditResource($project),
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
                    'user_id' => Auth::id(),
                    'description' => $this->history_description_added($change)
                ]);
            } else {
                if ($changes[$change] === null) {
                    $project->project_histories()->create([
                        'user_id' => Auth::id(),
                        'description' => $this->history_description_removed($change)
                    ]);
                } else {
                    $project->project_histories()->create([
                        'user_id' => Auth::id(),
                        'description' => $this->history_description_change($change)
                    ]);
                }
            }
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateProjectRequest  $request
     * @param  Project  $project
     * @return JsonResponse|RedirectResponse
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $update_properties = $request->only('name', 'description', 'number_of_participants', 'cost_center', 'sector_id', 'category_id', 'genre_id');

        // authorization
        if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
            && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
            && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $project->fill($update_properties);

        $this->add_to_history($project);

        $project->save();

        if ($request->assigned_user_ids) {
            $project->users()->sync(collect($request->assigned_user_ids));
        }

        if ($request->assigned_departments) {
            $project->departments()->sync(collect($request->assigned_departments));
        }

        return Redirect::back();
    }

    /**
     * Duplicates the project whose id is passed in the request
     */
    public function duplicate(Project $project)
    {
        // authorization
        if ($project->users->isNotEmpty()) {
            if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
                && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
                && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        if ($project->departments->isNotEmpty()) {
            $project->departments->map(fn ($department) => $this->authorize('update', $department));
        }

        $newProject = Project::create([
            'name' => '(Kopie) ' . $project->name,
            'description' => $project->description,
            'number_of_participants' => $project->number_of_participants,
            'cost_center' => $project->cost_center,
            'sector_id' => $project->sector_id,
            'category_id' => $project->category_id,
            'genre_id' => $project->genre_id,
        ]);

        $project->checklists->map(function (Checklist $checklist) use ($newProject) {
            /** @var \App\Models\Checklist $replicated_checklist */
            $replicated_checklist = $checklist->replicate()->fill(['project_id' => $newProject->id]);
            $replicated_checklist->save();
            $replicated_checklist->departments()->sync($checklist->departments->pluck('id'));

            $checklist->tasks->map(function (Task $task) use ($replicated_checklist) {
                $replicated_task = $task->replicate(['deadline', 'done', 'done_at',])
                    ->fill(['checklist_id' => $replicated_checklist->id]);

                $replicated_task->save();
            });
        });

        $newProject->users()->attach([Auth::id() => ['is_admin' => true]]);

        $newProject->departments()->sync($project->departments->pluck('id'));
        $newProject->users()->sync($project->users->pluck('id'));

        $newProject->project_histories()->create([
            'user_id' => Auth::id(),
            'description' => 'Projekt angelegt'
        ]);

        $project->project_histories()->create([
            'user_id' => Auth::id(),
            'description' => 'Projekt wurde dupliziert'
        ]);

        return Redirect::route('projects.show', $newProject->id)->with('success', 'Project created.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Project  $project
     * @return RedirectResponse
     */
    public function destroy(Project $project)
    {
        $project->load('events');

        $project->events()->delete();

        foreach ($project->checklists() as $checklist) {
            $checklist->tasks()->delete();
        }

        $project->checklists()->delete();

        $project->delete();

        return Redirect::route('projects')->with('success', 'Project moved to trash');
    }

    public function forceDelete(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->forceDelete();
        $project->events()->withTrashed()->forceDelete();

        return Redirect::route('projects.trashed')->with('success', 'Project deleted');
    }

    public function restore(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->restore();
        $project->events()->withTrashed()->restore();

        return Redirect::route('projects.trashed')->with('success', 'Project restored');
    }

    public function getTrashed()
    {
        return inertia('Trash/Projects', [
            'trashed_projects' => ProjectIndexResource::collection(Project::onlyTrashed()->get())->resolve()
        ]);
    }


}
