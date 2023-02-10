<?php

namespace App\Http\Controllers;

use App\Models\Comment;
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

        $projectFile =$project->project_files()->create([
            'name' => $original_name,
            'basename' => $basename,
        ]);

        $projectFile->accessing_users()->sync(collect($request->accessibleUsers));

        $projectFile->accessing_users()->save(Auth::user());

        if($request->comment){
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $projectFile->id
            ]);
            $projectFile->comments()->save($comment);
        }


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
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param ProjectFile $projectFile
     * @return RedirectResponse
     */
    public function update(Request $request, ProjectFile $projectFile): RedirectResponse
    {

        if ($request->get('accessibleUsers')) {
            $projectFile->accessing_users()->sync(collect($request->accessibleUsers));
        }

        if($request->file('file')) {
            Storage::delete('project_files/'. $projectFile->basename);
            $file = $request->file('file');
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20).$original_name;

            $projectFile->basename = $basename;
            $projectFile->name = $original_name;

            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $projectFile->id
            ]);
            $projectFile->comments()->save($comment);
            $projectFile->save();

            Storage::putFileAs('project_files', $file, $basename);
        }

        return Redirect::back();


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
