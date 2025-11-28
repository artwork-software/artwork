<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Project\Services\ProjectManagementBuilderService;
use Artwork\Modules\Project\Models\Component;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectManagementBuilderController extends Controller
{
    public function __construct(private readonly ProjectManagementBuilderService $projectManagementBuilderService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Settings/ProjectManagementBuilder/Index', [
            'componentsInGrid' => $this->projectManagementBuilderService->getProjectManagementBuilder(),
            'availableComponents' => Component::query()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'special'])
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Component $component): void
    {
        ProjectManagementBuilder::create([
            'name' => $component->name,
            'order' => $request->integer('order'),
            'is_active' => true,
            'type' => $component->type,
            'component_id' => $component->id,
            'deletable' => true
        ]);

        // reorder all other components
        $this->projectManagementBuilderService->reorderComponents();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectManagementBuilder $component): void
    {
        $component->delete();
    }

    public function updateOrder(Request $request): void
    {
        $components = $request->collect('components');
        $this->projectManagementBuilderService->updateOrder($components);
    }
}
