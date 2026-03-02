<?php

namespace App\Http\Controllers;

use Artwork\Modules\ArtistResidency\Enums\TypOfRoom;
use Artwork\Modules\Checklist\Http\Resources\ChecklistIndexResource;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Http\Requests\StoreProjectPrintLayoutRequest;
use Artwork\Modules\Project\Http\Requests\UpdateProjectPrintLayoutRequest;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Services\ProjectPrintLayoutService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Enums\ServiceProviderTypes;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use stdClass;

class ProjectPrintLayoutController extends Controller
{
    public function __construct(
        private readonly ProjectPrintLayoutService $projectService,
        private readonly ProjectTabService $projectTabService,
        private readonly UserService $userService
    )
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $excluded = [6, 7, 9, 13, 15, 17];

        // Komponentenlisten verschlankt und gecacht (10 Minuten)
        $components = Cache::remember('print_layout_components_not_special', 600, function () use ($excluded) {
            return Component::notSpecial()
                ->without(['users', 'departments'])
                ->whereNotIn('id', $excluded)
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled'])
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->groupBy('type');
        });

        $componentsSpecial = Cache::remember('print_layout_components_special', 600, function () use ($excluded) {
            return Component::isSpecial()
                ->without(['users', 'departments'])
                ->whereNotIn('id', $excluded)
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled'])
                ->orderBy('name')
                ->get();
        });

        $allComponents = Cache::remember('print_layout_all_components', 600, function () use ($excluded) {
            return Component::query()
                ->without(['users', 'departments'])
                ->whereNotIn('id', $excluded)
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled'])
                ->orderBy('type')
                ->orderBy('name')
                ->get();
        });

        return Inertia::render('Settings/ProjectPrintLayout/Index', [
            'components' => $components,
            'componentsSpecial' => $componentsSpecial,
            'layouts' => $this->projectService->getProjectPrintLayouts(),
            'allComponents' => $allComponents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectPrintLayoutRequest $request): void
    {
        if ($request->validated()) {
            $this->projectService->storeProjectPrintLayout($request->all());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, ProjectPrintLayout $projectPrintLayout): \Inertia\Response
    {
        $projectPrintLayout->load([
            'headerComponents',
            'bodyComponents',
            'footerComponents',
            'headerComponents.component',
            'bodyComponents.component',
            'footerComponents.component',
            'components.component',
        ]);
        $loadedProjectInformation = [];
        $projectComponents = collect([$project])->map(function ($project) use ($projectPrintLayout, $loadedProjectInformation ) {
            /** @var Project $project */
            $projectData = new stdClass(); // needed for the ProjectShowHeaderComponent
            $projectData->id = $project->id;
            $projectData->firstTabId = $this->projectTabService->getFirstProjectTabId();
            $projectData->project_managers = $project->managerUsers;
            $projectData->write_auth = $project->writeUsers;
            $projectData->delete_permission_users = $project->delete_permission_users;
            $projectData->projectGroups = $project->groups;
            $projectData->groupProjects = Project::where('is_group', 1)->get();
            $projectData->projectsOfGroup = $project->projectsOfGroup()->get();
            foreach ($projectPrintLayout->components as $component) {
                /** @var Component $componentFullData */
                $componentFullData = Component::find($component->component_id);
                switch ($componentFullData->type) {
                    case ProjectTabComponentEnum::PROJECT_TITLE->value:
                        $projectData->title = $project->name;
                        $projectData->key_visual_path = $project->key_visual_path;
                        $projectData->is_group = $project->is_group;
                        break;
                    case ProjectTabComponentEnum::PROJECT_STATUS->value:
                        $projectData->state = ProjectState::find($project->state);
                        break;
                    case ProjectTabComponentEnum::PROJECT_GROUP->value:
                        $projectData->group = $project->groups;
                        $projectData->is_group = $project->is_group;
                        break;
                    case ProjectTabComponentEnum::PROJECT_TEAM->value:
                        $projectData->team = $project->users;
                        break;
                    case ProjectTabComponentEnum::PROJECT_ATTRIBUTES->value:
                        $categories = $project->categories;
                        $genres = $project->genres;
                        $sectors = $project->sectors;
                        $projectData->attributes = [
                            'Categories' => $categories,
                            'Genres' => $genres,
                            'Sectors' => $sectors
                        ];
                        break;
                    case ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING->value:
                        $projectData->shift_relevant_event_types = $project->shiftRelevantEventTypes;
                        break;
                    case ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS->value:
                        $projectData->shift_contact = $project->shift_contact;
                        break;
                    case ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION->value:
                        $projectData->shift_description = $project->shift_description;
                        break;
                    case ProjectTabComponentEnum::PROJECT_BUDGET_DEADLINE->value:
                        $projectData->budget_deadline = $project->budget_deadline ?
                            Carbon::parse($project->budget_deadline)->translatedFormat('D, d F Y') : null;
                        break;
                    case ProjectTabComponentEnum::BUDGET_INFORMATIONS->value:
                        $projectData->cost_center = $project->costCenter;
                        $projectData->gema = $project->gema;
                        $projectData->cost_center_description = $project->cost_center_description;
                        break;
                    case ProjectTabComponentEnum::BULK_EDIT->value:
                        $eventsUnSorted = $project->events()->without([
                            'series',
                            'event_type',
                            'subEvents',
                            'creator',
                            'status'
                        ])->get()->map(
                        /** @return array<string, mixed> */
                            function (Event $event): array {
                                return array_merge(
                                    $event->toArray(),
                                    MinimalCalendarEventResource::make($event)->resolve()
                                );
                            }
                        );

                        $userBulkSortId = (int)$this->userService->getAuthUser()->getAttribute('bulk_sort_id');
                        $eventsSorted = $eventsUnSorted;

                        // userBulkSortId = 1 sort by room name asc
                        switch ($userBulkSortId) {
                            case 1:
                                $eventsSorted = $eventsUnSorted->sortBy('roomName');
                                break;
                            case 2:
                                // sort by event type name asc
                                $eventsSorted = $eventsUnSorted->sortBy('eventTypeName');
                                break;
                            case 3:
                                // sort by start time asc
                                $eventsSorted = $eventsUnSorted->sortBy('startTime');
                                break;
                        }
                        $eventsSorted = $eventsSorted->values();
                        $projectData->events = $eventsSorted;
                        $projectData->event_types = EventType::all();
                        $projectData->rooms = Room::all();
                        $projectData->event_statuses = EventStatus::all();
                        break;
                    case ProjectTabComponentEnum::ARTIST_RESIDENCIES->value:
                        $projectData->artist_residencies = $project->artistResidencies()
                            ->with(['accommodation'])->get();
                        break;
                    case ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS->value:
                        $projectData->project_files_all = $project->project_files;
                        break;
                    case ProjectTabComponentEnum::COMMENT_ALL_TAB->value:
                        $projectData->comments_all = $project->comments()
                            ->with('user')
                            ->orderBy('created_at', 'DESC')
                            ->get();
                        break;

                    case ProjectTabComponentEnum::CHECKLIST_ALL->value:
                        // change user checklist style temporary to kanban style
                        Inertia::share([
                            'user' => array_merge(session('user', []), ['checklist_style' => 'kanban'])
                        ]);
                        $projectData->opened_checklists = $project->checklists->pluck('id');
                        $userId = Auth::id();
                        $projectData->public_all_checklists = ChecklistIndexResource::collection(
                            $project->checklists
                                ->where('private', false)
                                ->filter(function ($checklist) use ($userId) {
                                    $isInChecklistUsers = $checklist->users->contains('id', $userId);
                                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                                        return $task->task_users->contains('id', $userId);
                                    });
                                    $isCreator = $checklist->user_id === $userId;
                                    return $isInChecklistUsers || $isInTaskUsers || $isCreator;
                                })
                        )->resolve();
                        $projectData->private_all_checklists = ChecklistIndexResource::collection(
                            $project->checklists
                                ->where('private', true)
                                ->filter(function ($checklist) use ($userId) {
                                    $isInChecklistUsers = $checklist->users->contains('id', $userId);
                                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                                        return $task->task_users->contains('id', $userId);
                                    });
                                    $isCreator = $checklist->user_id === $userId;
                                    return $isInChecklistUsers || $isInTaskUsers || $isCreator;
                                })
                        )->resolve();
                        break;
                    case ProjectTabComponentEnum::ARTIST_NAME_DISPLAY->value:
                        $projectData->artists = $project->artists;
                        break;
                    case ProjectTabComponentEnum::PROJECT_BASIC_DATA_DISPLAY->value:
                        $projectData->basic_data = [
                            'name' => $project->name,
                            'artists' => $project->artists,
                            'description' => $project->description,
                            'number_of_participants' => $project->number_of_participants,
                            'state' => ProjectState::find($project->state),
                            'categories' => $project->categories,
                            'genres' => $project->genres,
                            'sectors' => $project->sectors,
                        ];
                        break;
                    case ProjectTabComponentEnum::PROJECT_COST_CENTER_DISPLAY->value:
                        $projectData->cost_center = $project->costCenter;
                        break;
                    case ProjectTabComponentEnum::PROJECT_MATERIAL_ISSUE_COMPONENT->value:
                        $projectData->material_issues = [
                            'internal' => $project->contracts()
                                ->where('is_material_issue', true)
                                ->get(),
                        ];
                        break;
                    case ProjectTabComponentEnum::PROJECT_CONTRACTS_DOCUMENTS->value:
                        $projectData->contracts_documents = $project->contracts;
                        break;
                }



                if ($componentFullData) {
                    if (!$componentFullData?->special) {
                        $projectData->{$component->component->type}[$componentFullData->id] =
                            $componentFullData->projectValue()
                                ->where('project_id', $project->id)
                                ->first() ?? $componentFullData->data;
                    }
                }
            }

            return $projectData;
        });

        return Inertia::render('Projects/ProjectPrintLayoutWindow', [
            'project' => $projectComponents[0],
            'layout' => $projectPrintLayout,
            'components' => Component::all(),
            'loadedProjectInformation' => $loadedProjectInformation
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectPrintLayoutRequest $request, ProjectPrintLayout $projectPrintLayout)
    {
        if ($request->validated() && $projectPrintLayout->exists){
            $projectPrintLayout->update($request->all());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectPrintLayout $projectPrintLayout)
    {
        // first remove all components
        $projectPrintLayout->components()->delete();
        $projectPrintLayout->delete();
    }

    public function addComponent(ProjectPrintLayout $projectPrintLayout, Request $request){
        $projectPrintLayout->components()->create([
            'component_id' => $request->get('component_id'),
            'type' => $request->get('type'),
            'position' => $request->get('col'),
            'row' => $request->get('row'),
        ]);
    }

    public function destroyComponent(PrintLayoutComponents $printLayoutComponent){
        $printLayoutComponent->delete();
    }

    public function updateHeaderNote(Request $request, ProjectPrintLayout $projectPrintLayout){
        $projectPrintLayout->update([
            'notes' => [
                'header' => collect($request->input('header_note', [])),
                'footer' => collect($request->input('footer_note', [])),
            ]
        ]);
    }
}
