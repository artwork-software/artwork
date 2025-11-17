<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabArtistNameService;
use Illuminate\Http\JsonResponse;

class ProjectArtistNameController extends Controller
{
    public function __construct(
        private readonly ProjectTabArtistNameService $projectTabArtistNameService,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabArtistNameService->buildArtistNamePayload($project)
        );
    }
}

