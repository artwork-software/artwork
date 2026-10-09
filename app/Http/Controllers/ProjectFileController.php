<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Naming\StoredFileName;
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
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
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

    /**
     * Typen, die per ?inline=1 im Browser gerendert werden dürfen; alles andere geht als Attachment raus.
     *
     * @var list<string>
     */
    public const INLINE_PROJECT_FILE_MIME_TYPES = [
        'application/pdf',
        'image/avif',
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

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
        $tabId = $request->filled('tabId') ? $request->integer('tabId') : null;
        $this->authorize('create', [ProjectFile::class, $project, $tabId]);

        if (!Storage::exists("project_files")) {
            Storage::makeDirectory("project_files");
        }

        $file = $request->file('file');
        $this->handleFile(ArtworkFileTypes::PROJECT, $file);
        $original_name = $file->getClientOriginalName();
        $basename = StoredFileName::forUpload($file);

        Storage::putFileAs('project_files', $file, $basename);

        $projectFile = $project->project_files()->create([
            'tab_id' => $tabId,
            'name' => $original_name,
            'basename' => $basename,
            // Upload aus den Budget-Informationen (ProjectFileUploadModal): nur Freigabeliste und Admins
            'is_budget_document' => $tabId === null && $request->boolean('budgetDocument'),
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
            $notificationTitle = __('notification.project.file.permission_add', [], $projectFileUser->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
        SafeBroadcast::send(new UploadNewDocumentInProject($projectFile, $project->id));
    }

    public function download(Request $request, ProjectFile $projectFile): StreamedResponse
    {
        $this->authorize('view', $projectFile);

        $path = $projectFile->storagePath();

        if ($request->boolean('inline') && $this->canDisplayInline($path)) {
            return Storage::response($path, $projectFile->name);
        }

        return Storage::download($path, $projectFile->name);
    }

    private function canDisplayInline(string $path): bool
    {
        $mimeType = Storage::mimeType($path);

        return is_string($mimeType) && in_array($mimeType, self::INLINE_PROJECT_FILE_MIME_TYPES, true);
    }

    public function update(Request $request, ProjectFile $projectFile): RedirectResponse
    {
        $this->authorize('update', $projectFile);
        $original_name = '';

        // Auch eine geleerte Liste wird übernommen. Mit neuer Datei schickt Inertia FormData, darin fällt ein leeres
        // Array weg – deshalb zusätzlich das Markerfeld accessibleUsersSent (ProjectFileEditModal).
        if ($request->has('accessibleUsers') || $request->boolean('accessibleUsersSent')) {
            $userIds = collect($request->input('accessibleUsers', []))
                ->map(fn ($userId): int => (int) $userId);

            // Die hochladende Person ist nicht gespeichert. Wer schon freigegeben war, sperrt sich beim Bearbeiten
            // nicht selbst aus; wer nur korrigiert (Admin, Projektleitung), wird dadurch nicht neu eingetragen.
            $actingUserId = (int) Auth::id();
            if ($projectFile->accessingUsers()->whereKey($actingUserId)->exists()) {
                $userIds->push($actingUserId);
            }

            $projectFile->accessingUsers()->sync($userIds->unique()->values());
        }

        if ($request->file('file')) {
            $file = $request->file('file');
            $this->handleFile(ArtworkFileTypes::PROJECT, $file);
            Storage::delete($projectFile->storagePath());
            $original_name = $file->getClientOriginalName();
            $basename = StoredFileName::forUpload($file);

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
            $notificationTitle = __('notification.project.file.changed', [], $projectFileUser->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
            $notificationTitle = __('notification.project.file.deleted', [], $projectFileUser->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
        $projectFile->delete();

        // Erst nach dem Löschen melden: Clients laden ihre Liste daraufhin neu und dürfen die Datei nicht
        // mehr bekommen (das Event trägt nur Ids, siehe broadcastWith()).
        SafeBroadcast::send(new DeleteDocumentInProject($projectFile, $project->id));
        //return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $projectFile = ProjectFile::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $projectFile);

        Storage::delete($projectFile->storagePath());

        $projectFile->forceDelete();
        return Redirect::back();
    }
}
