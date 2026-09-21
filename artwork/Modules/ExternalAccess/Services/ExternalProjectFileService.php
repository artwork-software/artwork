<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\HandlesFileUpload;
use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotExternallyWritableException;
use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotInTabException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Events\DeleteDocumentInProject;
use Artwork\Modules\Project\Events\UploadNewDocumentInProject;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Dokumente im freigegebenen Tab für externe Personen: Liste, Upload, Download, Löschen eigener
 * Uploads. Dateien hängen wie intern am Projekt + tab_id; die interne Dokument-Komponente zeigt
 * sie über ihren Tab-Scope an. Größen-/Typ-Limits sind dieselben wie für interne Projektdateien.
 * Upload und Löschen eigener Uploads stehen zusätzlich unter der Einstellung "Dateiupload für Externe erlauben";
 * Liste/Download bleiben davon unberührt.
 */
class ExternalProjectFileService
{
    use HandlesFileUpload;

    public function __construct(
        protected readonly GeneralSettingsService $generalSettingsService,
        private readonly ExternalAccessSettingsResolver $settingsResolver,
        private readonly ExternalScopeResolver $scopeResolver,
    ) {
    }

    public function isUploadEnabled(): bool
    {
        return $this->settingsResolver->isFileUploadEnabled();
    }

    /**
     * @throws AuthorizationException
     */
    public function assertUploadEnabled(): void
    {
        if (!$this->isUploadEnabled()) {
            throw new AuthorizationException(__('File upload for external accesses is disabled.'));
        }
    }

    /**
     * @return Collection<int, ProjectFile>
     */
    public function listFiles(Project $project, ProjectTab $tab, Component $component): Collection
    {
        $this->assertDocumentComponentInTab($component, $tab);

        return $this->filesQuery($project, $tab)
            ->with('externalAccess:id,email,crm_contact_id', 'externalAccess.crmContact:id,display_name')
            ->orderByDesc('created_at')
            ->get();
    }

    public function upload(
        ExternalAccess $external,
        Project $project,
        ProjectTab $tab,
        Component $component,
        UploadedFile $file,
    ): ProjectFile {
        $this->assertUploadEnabled();
        $this->assertDocumentComponentInTab($component, $tab);
        $this->handleFile(ArtworkFileTypes::PROJECT, $file);

        if (!Storage::exists('project_files')) {
            Storage::makeDirectory('project_files');
        }

        $originalName = $file->getClientOriginalName();
        $basename = StoredFileName::forUpload($file);
        Storage::putFileAs('project_files', $file, $basename);

        /** @var ProjectFile $projectFile */
        $projectFile = $project->project_files()->create([
            'tab_id' => $tab->id,
            'name' => $originalName,
            'basename' => $basename,
            'external_access_id' => $external->id,
        ]);

        activity('external_project_file')
            ->performedOn($projectFile)
            ->causedBy($external)
            ->withProperties([
                'project_id' => $project->id,
                'project_tab_id' => $tab->id,
                'component_id' => $component->id,
                'file_name' => $originalName,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('file_uploaded');

        broadcast(new UploadNewDocumentInProject($projectFile, $project->id));

        return $projectFile;
    }

    public function findDownloadable(Project $project, ProjectTab $tab, int $fileId): ProjectFile
    {
        /** @var ProjectFile|null $file */
        $file = $this->filesQuery($project, $tab)->whereKey($fileId)->first();
        if ($file === null) {
            throw new NotFoundHttpException();
        }

        return $file;
    }

    /**
     * Externe löschen nur eigene Uploads, und nur solange der Upload-Schalter aktiv ist.
     */
    public function deleteOwn(ExternalAccess $external, Project $project, ProjectTab $tab, int $fileId): void
    {
        $this->assertUploadEnabled();

        /** @var ProjectFile|null $file */
        $file = $this->filesQuery($project, $tab)
            ->whereKey($fileId)
            ->where('external_access_id', $external->id)
            ->first();
        if ($file === null) {
            throw new NotFoundHttpException();
        }

        $name = $file->name;
        $file->delete();

        activity('external_project_file')
            ->performedOn($file)
            ->causedBy($external)
            ->withProperties([
                'project_id' => $project->id,
                'project_tab_id' => $tab->id,
                'file_name' => $name,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('file_deleted');

        broadcast(new DeleteDocumentInProject($file, $project->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(ProjectFile $file, ExternalAccess $external): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'file_size' => $file->file_size,
            'storage_available' => $file->storage_available,
            'created_at' => $file->created_at?->toIso8601String(),
            'uploaded_by_me' => (int) $file->external_access_id === (int) $external->id,
            'uploaded_externally' => $file->external_access_id !== null,
        ];
    }

    private function filesQuery(Project $project, ProjectTab $tab)
    {
        return $project->project_files()->where('tab_id', $tab->id);
    }

    private function assertDocumentComponentInTab(Component $component, ProjectTab $tab): void
    {
        if ((string) $component->type !== ProjectTabComponentEnum::PROJECT_DOCUMENTS->value) {
            throw new ComponentNotExternallyWritableException($component);
        }
        if (!$this->scopeResolver->componentBelongsToTab($component->id, $tab->id)) {
            throw new ComponentNotInTabException($component, $tab);
        }
    }
}
