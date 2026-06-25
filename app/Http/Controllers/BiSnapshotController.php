<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\StoreBiSnapshotRequest;
use Artwork\Modules\BusinessIntelligence\Models\BiSnapshot;
use Artwork\Modules\BusinessIntelligence\Services\BiSnapshotService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Http\JsonResponse;

class BiSnapshotController extends Controller
{
    public function __construct(
        private readonly BiSnapshotService $biSnapshotService
    ) {
    }

    public function index(Project $project): JsonResponse
    {
        return response()->json($this->biSnapshotService->getByProjectId($project->id));
    }

    public function store(StoreBiSnapshotRequest $request, Project $project): JsonResponse
    {
        $snapshot = $this->biSnapshotService->create(
            $project,
            $request->input('name'),
            $request->input('snapshot_date'),
            $request->user()->id
        );

        return response()->json($snapshot->load('creator'), 201);
    }

    public function show(Project $project, BiSnapshot $biSnapshot): JsonResponse
    {
        // IDOR-Schutz: Snapshot muss zum Projekt aus der URL gehören.
        abort_unless((int) $biSnapshot->project_id === (int) $project->id, 404);

        return response()->json($biSnapshot->load('creator'));
    }

    public function destroy(Project $project, BiSnapshot $biSnapshot): JsonResponse
    {
        abort_unless((int) $biSnapshot->project_id === (int) $project->id, 404);

        $this->biSnapshotService->delete($biSnapshot);

        return response()->json(null, 204);
    }
}
