<?php

namespace Artwork\Modules\Workflow\Http\Controllers;

use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflowService
    ) {
    }

    public function create(): Response
    {
        $definitions = WorkflowDefinition::with('currentConfig')
            ->where('is_active', true)
            ->whereHas('currentConfig')
            ->get()
            ->filter(fn($definition) => !$definition->hasReachedMaxInstances());

        return Inertia::render('Workflow/Create', [
            'definitions' => $definitions,
        ]);
    }

    public function index(): Response
    {
        $definitions = WorkflowDefinition::with('currentConfig')->get();
        $instances = WorkflowInstance::with('workflowDefinitionConfig.workflowDefinition')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Workflow/Index', [
            'definitions' => $definitions,
            'instances' => $instances,
        ]);
    }

    public function showDefinition(WorkflowDefinition $definition): Response
    {
        $definition->load('currentConfig');

        return Inertia::render('Workflow/Definition', [
            'definition' => $definition,
        ]);
    }

    public function showInstance(WorkflowInstance $instance): Response
    {
        $instance->load('workflowDefinitionConfig.workflowDefinition', 'workflowLogs');
        $availableTransitions = $this->workflowService->getAvailableTransitions($instance);

        return Inertia::render('Workflow/Instance', [
            'instance' => $instance,
            'availableTransitions' => $availableTransitions,
        ]);
    }

    public function executeTransition(Request $request, WorkflowInstance $instance): RedirectResponse
    {
        $request->validate([
            'transition' => 'required|string',
        ]);

        $success = $this->workflowService->executeTransition($instance, $request->transition);

        if ($success) {
            return redirect()->back()->with('success', 'Transition executed successfully.');
        }

        return redirect()->back()->with('error', 'Failed to execute transition.');
    }

    public function createDefinition(): Response
    {
        return Inertia::render('Workflow/CreateDefinition');
    }

    public function storeDefinition(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'config' => 'required|array',
        ]);

        try {
            $definition = $this->workflowService->createDefinition(
                $request->name,
                $request->type,
                $request->config
            );

            return redirect()->route('workflow.definitions.show', $definition)
                ->with('success', 'Workflow definition created successfully.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()
                ->with('error', 'Failed to create workflow definition: ' . $e->getMessage())
                ->withInput();
        }
    }
}
