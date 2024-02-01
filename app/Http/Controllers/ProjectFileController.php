<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Http\Requests\FileUpload;
use Artwork\Modules\Project\Models\Comment;

use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
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
    protected ?NewHistoryService $history = null;

    protected ?NotificationService $notificationService = null;

    public function __construct()
    {
        $this->history = new NewHistoryService('Artwork\Modules\Project\Models\Project');
        $this->notificationService = new NotificationService();
    }

    public function store(FileUpload $request, Project $project, ProjectController $projectController): RedirectResponse
    {
        $this->authorize('view', $project);

        if (!Storage::exists("project_files")) {
            Storage::makeDirectory("project_files");
        }

        $file = $request->file('file');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('project_files', $file, $basename);

        $projectFile = $project->project_files()->create([
            'name' => $original_name,
            'basename' => $basename,
        ]);

        $projectFile->accessingUsers()->sync(collect($request->accessibleUsers));

        if (is_array($request->accessibleUsers)) {
            if (!in_array(Auth::id(), $request->accessibleUsers)) {
                $projectFile->accessingUsers()->save(Auth::user());
            }
        } else {
            $projectFile->accessingUsers()->save(Auth::user());
        }

        if ($request->comment) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $projectFile->id
            ]);
            $projectFile->comments()->save($comment);
        }

        $this->history->createHistory($project->id, 'Datei ' . $original_name . ' hinzugefügt', 'public_changes');
        $projectController->setPublicChangesNotification($project->id);

        $projectFileUsers =  $projectFile->accessingUsers()->get();
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
                'href' => route('projects.show.budget', $project->id),
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(
            NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED
        );
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setProjectId($project->id);
        $this->notificationService->setDescription($notificationDescription);

        foreach ($projectFileUsers as $projectFileUser) {
            $this->notificationService->setNotificationTo($projectFileUser);
            $this->notificationService->createNotification();
        }

        return Redirect::back();
    }

    public function download(ProjectFile $projectFile): StreamedResponse
    {
        $this->authorize('view', $projectFile->project);

        return Storage::download('project_files/' . $projectFile->basename, $projectFile->name);
    }

    public function update(Request $request, ProjectFile $projectFile): RedirectResponse
    {
        $original_name = '';

        if ($request->get('accessibleUsers')) {
            $projectFile->accessingUsers()->sync(collect($request->accessibleUsers));
        }

        if ($request->file('file')) {
            Storage::delete('project_files/' . $projectFile->basename);
            $file = $request->file('file');
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            $projectFile->basename = $basename;
            $projectFile->name = $original_name;

            Storage::putFileAs('project_files', $file, $basename);
        }

        if ($request->get('comment')) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $projectFile->id
            ]);
            $projectFile->comments()->save($comment);
        }

        $projectFile->save();

        $project = $projectFile->project()->first();
        $projectFileUsers =  $projectFile->accessingUsers()->get();
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
                'href' => $project ? route('projects.show.budget', $project->id) : null,
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(
            NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED
        );
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setProjectId($project->id);
        $this->notificationService->setDescription($notificationDescription);

        foreach ($projectFileUsers as $projectFileUser) {
            $this->notificationService->setNotificationTo($projectFileUser);
            $this->notificationService->createNotification();
        }
        return Redirect::back();
    }

    public function destroy(ProjectFile $projectFile, ProjectController $projectController): RedirectResponse
    {
        $this->authorize('view', $projectFile->project);
        $project = $projectFile->project()->first();
        $this->history->createHistory($project->id, 'Datei ' . $projectFile->name . ' gelöscht', 'public_changes');
        $projectController->setPublicChangesNotification($project->id);

        $projectFileUsers =  $projectFile->accessingUsers()->get();
        $notificationTitle = 'Ein Dokument wurde gelöscht';
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
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
                'href' => $project ? route('projects.show.budget', $project->id) : null,
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(
            NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED
        );
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setProjectId($project->id);

        foreach ($projectFileUsers as $projectFileUser) {
            $this->notificationService->setNotificationTo($projectFileUser);
            $this->notificationService->createNotification();
        }
        $projectFile->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $projectFile = ProjectFile::onlyTrashed()->findOrFail($id);
        $this->authorize('view', $projectFile->project);

        Storage::delete('project_files/' . $projectFile->basename);

        $projectFile->forceDelete();
        return Redirect::back();
    }
}
