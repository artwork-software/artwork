<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ProjectFileController extends Controller
{
    protected ?HistoryController $history = null;

    public function __construct()
    {
        $this->history = new HistoryController('App\Models\Project');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('view', $project);

        if (!Storage::exists("project_files")) {
            Storage::makeDirectory("project_files");
        }

        $file = $request->file('file');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20).$original_name;

        Storage::putFileAs('project_files', $file, $basename);

        $project->project_files()->create([
            'name' => $original_name,
            'basename' => $basename,
        ]);

        $this->history->createHistory($project->id, 'Datei ' . $original_name . ' hinzugefügt');

        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param ProjectFile $projectFile
     * @return StreamedResponse
     * @throws AuthorizationException
     */
    public function download(ProjectFile $projectFile): StreamedResponse
    {
        $this->authorize('view', $projectFile->project);

        return Storage::download('project_files/'. $projectFile->basename, $projectFile->name);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ProjectFile $projectFile
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(ProjectFile $projectFile)
    {
        $this->authorize('view', $projectFile->project);
        $project = $projectFile->project()->first();
        $this->history->createHistory($project->id, 'Datei ' . $projectFile->name . ' gelöscht');

        $projectFile->delete();
        return Redirect::back();
    }

    public function force_delete(int $id): \Illuminate\Http\RedirectResponse
    {

        $projectFile = ProjectFile::onlyTrashed()->findOrFail($id);
        $this->authorize('view', $projectFile->project);

        Storage::delete('project_files/'. $projectFile->basename);

        $projectFile->forceDelete();
        return Redirect::back();
    }
}
