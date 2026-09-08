<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppProjectFileController extends Controller
{
    /**
     * Streams a project file for a short-lived signed URL (generated in
     * AppSystemComponentService::files). The `signed` middleware is the
     * gate — the device opens these in a browser without the API token.
     */
    public function __invoke(ProjectFile $projectFile): StreamedResponse
    {
        return Storage::download($projectFile->storagePath(), $projectFile->name);
    }
}
