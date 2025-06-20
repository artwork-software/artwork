<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\HandlesFileUpload;
use Artwork\Core\Http\Requests\FileUpload;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Events\DeleteDocumentInProject;
use Artwork\Modules\Project\Events\UploadNewDocumentInProject;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
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
    use HandlesFileUpload;

    public function __construct(
        private readonly ChangeService $changeService,
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
        protected readonly GeneralSettingsService $generalSettingsService
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function store(FileUpload $request, Project $project, ProjectController $projectController): void
    {
        $this->authorize('create', [ProjectFile::class, $project]);

        if (!Storage::exists("project_files")) {
            Storage::makeDirectory("project_files");
        }

        $file = $request->file('file');
        $this->handleFile(ArtworkFileTypes::PROJECT, $file);
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('project_files', $file, $basename);

        $projectFile = $project->project_files()->create([
            'tab_id' => $request->input('tabId'),
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

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('public_changes')
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey('Added file')
                ->setTranslationKeyPlaceholderValues([$original_name])
        );

        $projectController->setPublicChangesNotification($project->id);

        $projectFileUsers =  $projectFile->accessingUsers()->get();

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED
        );

        $this->notificationService->setProjectId($project->id);

        foreach ($projectFileUsers as $projectFileUser) {
            $notificationTitle = __('notifications.project.file.permission_add', [], $projectFileUser->language);
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
                    'href' => route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ),
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($projectFileUser);
            $this->notificationService->createNotification();
        }

        //return Redirect::back();
        broadcast(new UploadNewDocumentInProject($projectFile, $project->id));
    }

    public function download(ProjectFile $projectFile): StreamedResponse
    {
        $this->authorize('view', $projectFile);

        return Storage::download('project_files/' . $projectFile->basename, $projectFile->name);
    }

    public function update(Request $request, ProjectFile $projectFile): RedirectResponse
    {
        $this->authorize('update', $projectFile);
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
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED
        );
        $this->notificationService->setProjectId($project->id);

        foreach ($projectFileUsers as $projectFileUser) {
            $notificationTitle = __('notifications.project.file.changed', [], $projectFileUser->language);
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
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($projectFileUser);
            $this->notificationService->createNotification();
        }
        return Redirect::back();
    }

    public function destroy(ProjectFile $projectFile, ProjectController $projectController): void
    {
        $this->authorize('delete', $projectFile);
        $project = $projectFile->project()->first();

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('public_changes')
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey('Deleted file')
                ->setTranslationKeyPlaceholderValues([$projectFile->name])
        );

        $projectController->setPublicChangesNotification($project->id);

        $projectFileUsers =  $projectFile->accessingUsers()->get();

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED
        );
        $this->notificationService->setProjectId($project->id);

        foreach ($projectFileUsers as $projectFileUser) {
            $notificationTitle = __('notifications.project.file.deleted', [], $projectFileUser->language);
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
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($projectFileUser);
            $this->notificationService->createNotification();
        }
        broadcast(new DeleteDocumentInProject($projectFile, $project->id));

        $projectFile->delete();
        //return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $projectFile = ProjectFile::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $projectFile);

        Storage::delete('project_files/' . $projectFile->basename);

        $projectFile->forceDelete();
        return Redirect::back();
    }
}
