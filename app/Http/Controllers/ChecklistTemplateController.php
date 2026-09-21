<?php

namespace App\Http\Controllers;

use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Http\Resources\ChecklistTemplateIndexResource;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Checklist\Services\ChecklistTemplateService;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\TaskTemplate\Services\TaskTemplateService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class ChecklistTemplateController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly ChecklistTemplateService $checklistTemplateService,
        private readonly TaskTemplateService $taskTemplateService
    ) {
        $this->authorizeResource(ChecklistTemplate::class);
    }

    public function index(): Response|ResponseFactory
    {
        return inertia('ChecklistTemplates/ChecklistTemplateManagement', [
            'checklist_templates' => ChecklistTemplateIndexResource::collection(
                ChecklistTemplate::with(['task_templates', 'user', 'users'])->get()
            )->resolve(),
        ]);
    }

    /**
     * @param SearchRequest $request
     * @return array<string, mixed>
     */
    public function search(SearchRequest $request): array
    {
        return ChecklistTemplateIndexResource::collection(
            ChecklistTemplate::search($request->input('query'))->get()
        )->resolve();
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('ChecklistTemplates/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        // Sicherheits-Audit 21.09.2026 (E): user_id kam aus dem Body, task_templates ungeprüft.
        $request->validate([
            'checklist_id' => ['nullable', 'integer', 'exists:checklists,id'],
            'name' => ['required_without:checklist_id', 'nullable', 'string', 'max:255'],
            'users' => ['nullable', 'array'],
            'users.*.id' => ['required', 'integer', 'exists:users,id'],
            'task_templates' => ['nullable', 'array'],
            'task_templates.*.name' => ['required', 'string', 'max:255'],
            'task_templates.*.description' => ['nullable', 'string', 'max:65535'],
            'task_templates.*.deadline_days_after_creation' => ['nullable', 'integer', 'min:0', 'max:36500'],
        ]);

        if ($request->checklist_id) {
            $this->createFromChecklist($request);
        } else {
            $this->createFromScratch($request);
        }

        return Redirect::route('checklist_templates.management');
    }

    protected function createFromChecklist(Request $request): void
    {
        $checklist = Checklist::where('id', $request->checklist_id)->first();

        $checklist_template = ChecklistTemplate::create([
            'name' => $checklist->name,
            'user_id' => $this->authManager->id()
        ]);

        foreach ($checklist->tasks as $task) {
            TaskTemplate::create([
                'name' => $task->name,
                'description' => $task->description,
                'done' => false,
                'checklist_template_id' => $checklist_template->id
            ]);
        }

        $checklist_template->users()->sync(collect($checklist->users)->pluck('id'));
    }

    protected function createFromScratch(Request $request): void
    {
        $checklist_template = ChecklistTemplate::create([
            'name' => $request->name,
            // Ersteller*in ist immer die angemeldete Person, nie ein Body-Wert
            'user_id' => $this->authManager->id()
        ]);

        $checklist_template->users()->sync(Collection::make($request->users)->pluck('id'));

        if ($request->task_templates) {
            $checklist_template->task_templates()->createMany(
                self::onlyTaskTemplateFields($request->task_templates)
            );
        }
    }

    public function update(Request $request, ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'users' => ['nullable', 'array'],
            'users.*.id' => ['required', 'integer', 'exists:users,id'],
            'task_templates' => ['nullable', 'array'],
            'task_templates.*.name' => ['required', 'string', 'max:255'],
            'task_templates.*.description' => ['nullable', 'string', 'max:65535'],
            'task_templates.*.deadline_days_after_creation' => ['nullable', 'integer', 'min:0', 'max:36500'],
        ]);

        $checklistTemplate->update($request->only('name'));

        if ($request->has('users')) {
            $userIdsToSync = Collection::make($request->users)->pluck('id');
            $checklistTemplate->users()->sync($userIdsToSync);
        }

        if ($request->task_templates) {
            $userIdsToSync = Collection::make($request->users)->pluck('id');
            $checklistTemplate->task_templates()->delete();
            foreach (self::onlyTaskTemplateFields($request->task_templates) as $task_template) {
                $task_template_new = $checklistTemplate->task_templates()->create($task_template);
                $task_template_new->task_users()->sync($userIdsToSync);
            }
        }

        return Redirect::back();
    }

    /**
     * Nur die Felder, die eine Aufgabenvorlage aus dem Body übernehmen darf (kein checklist_template_id,
     * keine Fremd-IDs).
     *
     * @param array<int, array<string, mixed>> $taskTemplates
     * @return array<int, array<string, mixed>>
     */
    private static function onlyTaskTemplateFields(array $taskTemplates): array
    {
        return array_map(
            static fn (array $taskTemplate): array => array_intersect_key(
                $taskTemplate,
                array_flip(['name', 'description', 'deadline_days_after_creation'])
            ),
            array_values($taskTemplates)
        );
    }

    public function destroy(ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        $checklistTemplate->delete();

        return Redirect::back();
    }

    public function duplicate(ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        // nicht von authorizeResource abgedeckt
        $this->authorize('create', ChecklistTemplate::class);

        $this->taskTemplateService->duplicateTaskTemplates(
            $checklistTemplate,
            $this->checklistTemplateService->duplicate(
                $checklistTemplate,
                $this->authManager->id()
            )
        );

        return Redirect::back();
    }
}
