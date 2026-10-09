<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppProjectFileController extends Controller
{
    /**
     * Streams a project file for a short-lived signed URL (generated in
     * AppSystemComponentService::files). The device opens these in a browser
     * without the API token: the `signed` middleware protects the link, and the
     * user id signed into it lets the download re-check ProjectFilePolicy::view
     * (rights or the share list may have changed since the link was issued).
     */
    public function __invoke(Request $request, ProjectFile $projectFile): StreamedResponse
    {
        $user = User::query()->find($request->integer('user'));
        abort_if($user === null || Gate::forUser($user)->denies('view', $projectFile), 403);

        $path = $projectFile->storagePath();
        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, $projectFile->name);
    }
}
