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
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectDocumentPlacementService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
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
        private readonly ProjectDocumentPlacementService $placementService,
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
            ->with('externalAccess:id,email,name,crm_contact_id', 'externalAccess.crmContact:id,display_name')
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

        // Grenzen des Upload-Bereichs (Platzierung der Komponente in diesem Tab) wie intern
        $placementKey = $this->placementKeyInTab($component, $tab);
        if ($placementKey !== null) {
            $this->placementService->assertUploadAllowed($project, $placementKey, $file);
        }

        if (!Storage::exists('project_files')) {
            Storage::makeDirectory('project_files');
        }

        $originalName = $file->getClientOriginalName();
        $basename = StoredFileName::forUpload($file);
        Storage::putFileAs('project_files', $file, $basename);

        /** @var ProjectFile $projectFile */
        $projectFile = $project->project_files()->create([
            'tab_id' => $tab->id,
            'uploaded_via' => $placementKey,
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

        SafeBroadcast::send(new UploadNewDocumentInProject($projectFile, $project->id));

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

        SafeBroadcast::send(new DeleteDocumentInProject($file, $project->id));
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

    /**
     * Dateien des Tabs: Standard-Sichtbarkeit über den Upload-Tab, sonst ausdrücklich in einem Upload-Bereich
     * dieses Tabs sichtbar (visible_in).
     */
    private function filesQuery(Project $project, ProjectTab $tab)
    {
        $keysInTab = $this->placementService->placements()
            ->filter(fn (array $placement): bool => in_array((int) $tab->id, $placement['tab_ids'], true))
            ->pluck('key')
            ->all();

        return $project->project_files()->where(function (Builder $query) use ($tab, $keysInTab): void {
            $query->where(fn (Builder $legacy) => $legacy->whereNull('visible_in')->where('tab_id', $tab->id));
            foreach ($keysInTab as $key) {
                $query->orWhereJsonContains('visible_in', $key);
            }
        });
    }

    /**
     * Upload-Bereich der Komponente in diesem Tab (direkt im Tab oder in einem Ordner des Tabs).
     */
    private function placementKeyInTab(Component $component, ProjectTab $tab): ?string
    {
        $tabPlacementId = ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->where('project_tab_id', $tab->id)
            ->where('component_id', $component->id)
            ->value('id');
        if ($tabPlacementId !== null) {
            return ProjectDocumentPlacementService::keyFor((int) $tabPlacementId, false);
        }

        $folderPlacementId = DisclosureComponents::query()
            ->without('component')
            ->where('component_id', $component->id)
            ->whereIn(
                'disclosure_id',
                ComponentInTab::query()->without(['component', 'disclosureComponents'])
                    ->where('project_tab_id', $tab->id)->select('component_id')
            )
            ->value('id');

        return $folderPlacementId !== null
            ? ProjectDocumentPlacementService::keyFor((int) $folderPlacementId, true)
            : null;
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
