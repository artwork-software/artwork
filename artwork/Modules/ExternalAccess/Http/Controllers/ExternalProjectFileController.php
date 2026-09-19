<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Exceptions\ExternalAccessException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalProjectFileService;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExternalProjectFileController extends Controller
{
    public function __construct(
        private readonly ExternalProjectFileService $service,
    ) {
    }

    public function index(Request $request, Project $project, ProjectTab $tab, Component $component): JsonResponse
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        try {
            $files = $this->service->listFiles($project, $tab, $component)
                ->map(fn (ProjectFile $file) => $this->service->serialize($file, $external))
                ->values();
        } catch (ExternalAccessException $e) {
            return response()->json(['message' => __($e->getMessage())], 422);
        }

        return response()->json(['documents' => $files]);
    }

    public function store(Request $request, Project $project, ProjectTab $tab, Component $component): JsonResponse
    {
        $request->validate(['file' => ['required', 'file']]);

        /** @var ExternalAccess $external */
        $external = $request->user('external');

        try {
            $file = $this->service->upload($external, $project, $tab, $component, $request->file('file'));
        } catch (ExternalAccessException $e) {
            return response()->json(['message' => __($e->getMessage())], 422);
        }

        return response()->json(['document' => $this->service->serialize($file, $external)], 201);
    }

    public function download(Request $request, Project $project, ProjectTab $tab, int $file): StreamedResponse
    {
        $projectFile = $this->service->findDownloadable($project, $tab, $file);
        $path = 'project_files/' . $projectFile->basename;

        if ($request->boolean('inline')) {
            return Storage::response($path, $projectFile->name);
        }

        return Storage::download($path, $projectFile->name);
    }

    public function destroy(Request $request, Project $project, ProjectTab $tab, int $file): JsonResponse
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        $this->service->deleteOwn($external, $project, $tab, $file);

        return response()->json(['status' => 'deleted']);
    }
}
