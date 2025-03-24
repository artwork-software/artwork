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
use Artwork\Modules\ProjectPrintLayout\Http\Requests\StoreProjectPrintLayoutRequest;
use Artwork\Modules\ProjectPrintLayout\Http\Requests\UpdateProjectPrintLayoutRequest;
use Artwork\Modules\ProjectPrintLayout\Models\PrintLayoutComponents;
use Artwork\Modules\ProjectPrintLayout\Models\ProjectPrintLayout;
use Artwork\Modules\ProjectPrintLayout\Services\ProjectPrintLayoutService;

use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Enums\ServiceProviderTypes;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use stdClass;

class ProjectPrintLayoutController extends Controller
{
    public function __construct(
        private readonly ProjectPrintLayoutService $projectService,
        private readonly ProjectTabService $projectTabService,
        private readonly UserService $userService,
        private readonly ChecklistService $checklistService
    )
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Settings/ProjectPrintLayout/Index', [
            'components' => Component::notSpecial()->whereNotIn('id', [6,7,9,13,15,17])->get()->groupBy('type'),
            'componentsSpecial' => Component::isSpecial()->whereNotIn('id', [6,7,9,13,15,17])->get(),
            'layouts' => $this->projectService->getProjectPrintLayouts(),
            'allComponents' => Component::whereNotIn('id', [6,7,9,13,15,17])->get(),
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
                        $projectData->collecting_society = $project->collectingSociety;
                        $projectData->cost_center = $project->costCenter;
                        $projectData->own_copyright = $project->own_copyright;
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
                        Inertia::share([
                            'roomTypes' => TypOfRoom::cases(),
                            'serviceProviders' => ServiceProvider
                                ::where('type_of_provider', ServiceProviderTypes::HOUSING->value)
                                ->without(['contacts'])->get(),
                            'artist_residencies' => $project->artistResidencies()
                                ->with(['serviceProvider'])->get(),
                        ]);
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
                                    // Prüfen, ob der Benutzer in den Checklistenbenutzern ist
                                    $isInChecklistUsers = $checklist->users->contains('id', $userId);

                                    // Prüfen, ob der Benutzer in den Aufgabenbenutzern ist
                                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                                        return $task->task_users->contains('id', $userId);
                                    });

                                    // Prüfen, ob der Benutzer der Ersteller der Checkliste ist
                                    $isCreator = $checklist->user_id === $userId;

                                    return $isInChecklistUsers || $isInTaskUsers || $isCreator;
                                })
                        );
                        $projectData->private_all_checklists = ChecklistIndexResource::collection(
                            $project->checklists
                                ->where('private', true)
                                ->filter(function ($checklist) use ($userId) {
                                    // Prüfen, ob der Benutzer in den Checklistenbenutzern ist
                                    $isInChecklistUsers = $checklist->users->contains('id', $userId);

                                    // Prüfen, ob der Benutzer in den Aufgabenbenutzern ist
                                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                                        return $task->task_users->contains('id', $userId);
                                    });

                                    // Prüfen, ob der Benutzer der Ersteller der Checkliste ist
                                    $isCreator = $checklist->user_id === $userId;

                                    return $isInChecklistUsers || $isInTaskUsers || $isCreator;
                                })
                        );
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
