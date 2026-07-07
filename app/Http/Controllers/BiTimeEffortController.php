<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\StoreBiTimeEffortRequest;
use Artwork\Modules\BusinessIntelligence\Http\Requests\UpdateBiTimeEffortRequest;
use Artwork\Modules\BusinessIntelligence\Models\BiTimeEffort;
use Artwork\Modules\BusinessIntelligence\Repositories\BiTimeEffortRepository;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Http\JsonResponse;

class BiTimeEffortController extends Controller
{
    public function __construct(
        private readonly BiTimeEffortRepository $biTimeEffortRepository
    ) {
    }

    public function index(Project $project): JsonResponse
    {
        return response()->json($this->biTimeEffortRepository->getByProjectId($project->id));
    }

    public function store(StoreBiTimeEffortRequest $request, Project $project): JsonResponse
    {
        $effort = $this->biTimeEffortRepository->getNewModelInstance();
        $effort->fill(array_merge($request->validated(), [
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
        ]));
        $this->biTimeEffortRepository->save($effort);

        return response()->json($effort->load('user'), 201);
    }

    public function update(
        UpdateBiTimeEffortRequest $request,
        Project $project,
        BiTimeEffort $biTimeEffort
    ): JsonResponse {
        // IDOR-Schutz: TimeEffort muss zum Projekt aus der URL gehören.
        abort_unless((int) $biTimeEffort->project_id === (int) $project->id, 404);

        $this->biTimeEffortRepository->update($biTimeEffort, $request->validated());

        return response()->json($biTimeEffort->fresh()->load('user'));
    }

    public function destroy(Project $project, BiTimeEffort $biTimeEffort): JsonResponse
    {
        abort_unless((int) $biTimeEffort->project_id === (int) $project->id, 404);

        $this->biTimeEffortRepository->delete($biTimeEffort);

        return response()->json(null, 204);
    }
}
