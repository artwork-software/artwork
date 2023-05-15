<?php

namespace App\Http\Controllers;

use App\Enums\BudgetTypesEnum;
use App\Enums\NotificationConstEnum;
use App\Http\Requests\FileUpload;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ProjectFileController extends Controller
{
    protected ?NewHistoryService $history = null;
    protected ?NotificationService $notificationService = null;

    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\Project');
        $this->notificationService = new NotificationService();
    }

    /**
     * @throws AuthorizationException
     */
    public function store(FileUpload $request, Project $project): \Illuminate\Http\RedirectResponse
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

        if(!in_array(Auth::id(), $request->accessibleUsers)) {
            $projectFile->accessing_users()->save(Auth::user());
        }

        if($request->comment){
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $projectFile->id
            ]);
            $projectFile->comments()->save($comment);
        }


        $this->history->createHistory($project->id, 'Datei ' . $original_name . ' hinzugefügt', 'public_changes');
        $projectController = new ProjectController();
        $projectController->setPublicChangesNotification($project->id);

        $projectFileUsers =  $projectFile->accessing_users()->get();
        $notificationTitle = 'Ein Dokument wurde für dich freigegeben';
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $original_name,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' =>  $project->name,
                'href' => route('projects.show', $project->id),
            ]
        ];

        foreach ($projectFileUsers as $projectFileUser){
            $this->notificationService->createNotification($projectFileUser, $notificationTitle, $notificationDescription, NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED, 'green', [], false, '', null, $broadcastMessage, null, null, $project->id, null, null, null);
        }
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
        $original_name = '';

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

            Storage::putFileAs('project_files', $file, $basename);
        }

        if($request->get('comment')) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $projectFile->id
            ]);
            $projectFile->comments()->save($comment);
        }

        $projectFile->save();

        $project = $projectFile->project()->first();
        $projectFileUsers =  $projectFile->accessing_users()->get();
        $notificationTitle = 'Ein Dokument wurde geändert';
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $original_name === '' ? $projectFile->name : $original_name,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' =>  $project ? $project->name : '',
                'href' => $project ? route('projects.show', $project->id) : null,
            ]
        ];

        foreach ($projectFileUsers as $projectFileUser){
            $this->notificationService->createNotification($projectFileUser, $notificationTitle, $notificationDescription, NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED, 'green', [], false, '', null, $broadcastMessage, null, null, $project->id, null, null, null);
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
        $this->history->createHistory($project->id, 'Datei ' . $projectFile->name . ' gelöscht', 'public_changes');
        $projectController = new ProjectController();
        $projectController->setPublicChangesNotification($project->id);

        $projectFileUsers =  $projectFile->accessing_users()->get();
        $notificationTitle = 'Ein Dokument wurde gelöscht';
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'danger',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $projectFile->name,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' =>  $project ? $project->name : '',
                'href' => $project ? route('projects.show', $project->id) : null,
            ]
        ];

        foreach ($projectFileUsers as $projectFileUser){
            $this->notificationService->createNotification($projectFileUser, $notificationTitle, $notificationDescription, NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED, 'red', [], false, '', null, $broadcastMessage, null, null, $project->id, null, null, null);
        }
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
