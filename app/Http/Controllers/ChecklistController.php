<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Task;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use App\Http\Resources\ChecklistShowResource;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Support\Services\HistoryService;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectHistory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class ChecklistController extends Controller
{
    protected ?NewHistoryService $history = null;

    public function __construct(protected readonly ChecklistService $checklistService)
    {
        $this->authorizeResource(Checklist::class);
    }

    public function create(): ResponseFactory
    {
        return inertia('Checklists/Create');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('createProperties', Project::find($request->project_id));
        $this->checklistService->createFromRequest($request);

        return Redirect::back();
    }

    public function show(Checklist $checklist): Response|ResponseFactory
    {
        return inertia('Checklists/Show', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function edit(Checklist $checklist): Response|ResponseFactory
    {
        return inertia('Checklists/Edit', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function update(ChecklistUpdateRequest $request, Checklist $checklist): RedirectResponse
    {
        $this->checklistService->updateByRequest($checklist, $request);

        if ($request->missing('assigned_user_ids')) {
            return Redirect::back();
        }

        $this->checklistService->assignUsersById($checklist, $request->assigned_user_ids);

        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory(
            $checklist->project_id,
            'Checklist modified',
            [$checklist->name]
        );

        return Redirect::back();
    }

    public function destroy(Checklist $checklist, HistoryService $historyService): RedirectResponse
    {
        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory(
            $checklist->project_id,
            'Checklist removed',
            [$checklist->name]
        );
        $checklist->forceDelete();
        $historyService->checklistUpdated($checklist);

        return Redirect::back();
    }
}
