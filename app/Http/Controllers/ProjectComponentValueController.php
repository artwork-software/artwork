<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Http\Request;

class ProjectComponentValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
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
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectComponentValue $projectComponentValue): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectComponentValue $projectComponentValue): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, Component $component): void
    {
        /** @var \Artwork\Modules\User\Models\User $user */
        $user = $request->user();

        // Schreibrecht im Projekt + Komponenten-Einstellung (Spiegel von canEditComponent() im Frontend).
        abort_unless($user->can('writeComponent', [$project, $component]), 403);

        // Fehlendes data oder ein Array als text führten vorher zu TypeError/ErrorException (500).
        $request->validate([
            'data' => ['present', 'nullable', 'array'],
            // Zahlen aus Zahlenfeldern bleiben erlaubt (werden zu Text), nur Listen/Objekte nicht.
            'data.text' => ['sometimes', 'nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_array($value)) {
                    $fail(__('validation.string', ['attribute' => $attribute]));
                }
            }],
        ]);

        $data = $request->input('data') ?? [];
        // Rohtext; Umbrüche rendert das Frontend per white-space: pre-line.
        $valueInput = array_key_exists('text', $data) ? ['text' => (string) $data['text']] : $data;

        // Unique-Index (project_id, component_id): parallele Autosaves erzeugen keine Duplikate mehr.
        $value = ProjectComponentValue::query()->updateOrCreate(
            ['project_id' => $project->id, 'component_id' => $component->id],
            ['data' => $valueInput]
        );

        broadcast(new UpdateProjectComponentData($value, $project->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectComponentValue $projectComponentValue): void
    {
        //
    }
}
