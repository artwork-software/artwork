<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\System\FileHandling\MimeTypeList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Upload-Bereiche der Dateiupload-Komponente (früher "Dokumente"): jede Platzierung der Komponente in einem
 * Tab ("tab:<component_in_tabs.id>") oder Ordner ("folder:<disclosure_components.id>") ist ein Bereich mit
 * eigener Tab-Auswahl (scope) und eigenen Upload-Einstellungen.
 *
 * Sichtbarkeit einer Datei:
 * - visible_in = null (Altbestand/Standard): in allen Bereichen, deren Tab-Auswahl den Upload-Tab enthält
 * - visible_in = Liste: genau in diesen Bereichen
 *
 * Upload-Einstellungen je Bereich (upload_settings): allowed_extensions (Teilmenge der globalen Freigaben,
 * leer = alle global erlaubten), max_file_size_mb (höchstens das globale Limit), max_files (je Projekt,
 * gezählt werden die über diesen Bereich hochgeladenen Dateien).
 */
class ProjectDocumentPlacementService
{
    public const TAB_PREFIX = 'tab:';

    public const FOLDER_PREFIX = 'folder:';

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $placements = null;

    public function __construct(
        private readonly GeneralSettingsService $generalSettingsService,
    ) {
    }

    public static function keyFor(int $placementId, bool $inFolder): string
    {
        return ($inFolder ? self::FOLDER_PREFIX : self::TAB_PREFIX) . $placementId;
    }

    /**
     * Alle Upload-Bereiche: key, label, tab_ids (Tabs, in denen der Bereich angezeigt wird), scope, settings.
     *
     * @return Collection<int, array{key: string, label: string, tab_ids: list<int>, scope: list<int>, settings: array<string, mixed>}>
     */
    public function placements(): Collection
    {
        if ($this->placements !== null) {
            return $this->placements;
        }

        $type = ProjectTabComponentEnum::PROJECT_DOCUMENTS->value;

        $tabPlacements = ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->with('tab:id,name,order')
            ->whereHas('component', fn (Builder $query) => $query->where('type', $type))
            ->get()
            ->map(fn (ComponentInTab $placement): array => [
                'key' => self::keyFor($placement->id, false),
                'label' => (string) ($placement->tab?->name ?? ''),
                'tab_ids' => $placement->tab ? [$placement->tab->id] : [],
                'scope' => array_map('intval', (array) ($placement->scope ?? [])),
                'settings' => $this->normalizeSettings($placement->upload_settings),
                'order' => [(int) ($placement->tab?->order ?? 0), (int) $placement->order],
            ]);

        $folderPlacements = DisclosureComponents::query()
            ->without('component')
            ->with('disclosure:id,name,data')
            ->whereHas('component', fn (Builder $query) => $query->where('type', $type))
            ->get();

        // Tabs, in denen die jeweiligen Ordner liegen
        $folderTabs = ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->with('tab:id,name,order')
            ->whereIn('component_id', $folderPlacements->pluck('disclosure_id')->unique())
            ->get()
            ->groupBy('component_id');

        $folderPlacements = $folderPlacements->map(function (DisclosureComponents $placement) use ($folderTabs): array {
            $tabs = ($folderTabs->get($placement->disclosure_id) ?? collect())
                ->pluck('tab')
                ->filter();
            $folderLabel = (string) ($placement->disclosure?->data['label'] ?? $placement->disclosure?->name ?? '');

            return [
                'key' => self::keyFor($placement->id, true),
                'label' => trim($tabs->pluck('name')->join(', ') . ' › ' . $folderLabel, ' ›'),
                'tab_ids' => $tabs->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'scope' => array_map('intval', (array) ($placement->scope ?? [])),
                'settings' => $this->normalizeSettings($placement->upload_settings),
                'order' => [(int) ($tabs->min('order') ?? PHP_INT_MAX), (int) $placement->order],
            ];
        });

        $sorted = $tabPlacements->concat($folderPlacements)
            ->sortBy([fn (array $a, array $b): int => $a['order'] <=> $b['order']])
            ->map(function (array $placement): array {
                unset($placement['order']);

                return $placement;
            })
            ->values();

        // Gleichnamige Bereiche (mehrere Platzierungen in einem Tab, gleich benannte Tabs) durchnummerieren
        $labelCounts = $sorted->countBy('label');
        $seen = [];

        return $this->placements = $sorted->map(function (array $placement) use ($labelCounts, &$seen): array {
            $label = $placement['label'];
            if (($labelCounts[$label] ?? 0) > 1) {
                $seen[$label] = ($seen[$label] ?? 0) + 1;
                $placement['label'] = $label . ' (' . $seen[$label] . ')';
            }

            return $placement;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        return $this->placements()->firstWhere('key', $key);
    }

    /**
     * Standard-Sichtbarkeit eines Uploads aus einem Tab: alle Bereiche, deren Tab-Auswahl ihn enthält.
     *
     * @return list<string>
     */
    public function defaultKeysForTab(?int $tabId): array
    {
        if ($tabId === null) {
            return [];
        }

        return $this->placements()
            ->filter(fn (array $placement): bool => in_array($tabId, $placement['scope'], true))
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * Bereiche, in denen die Datei aktuell erscheint.
     *
     * @return list<string>
     */
    public function visibleKeysOf(ProjectFile $file): array
    {
        return is_array($file->visible_in)
            ? array_values(array_map('strval', $file->visible_in))
            : $this->defaultKeysForTab($file->tab_id);
    }

    /**
     * Tabs, über die eine Datei sichtbar ist: Upload-Tab plus die Tabs der ausdrücklich gewählten Bereiche.
     *
     * @return list<int>
     */
    public function tabIdsOf(ProjectFile $file): array
    {
        $tabIds = $file->tab_id !== null ? [(int) $file->tab_id] : [];
        if (is_array($file->visible_in)) {
            foreach ($this->placements()->whereIn('key', $file->visible_in) as $placement) {
                $tabIds = [...$tabIds, ...$placement['tab_ids']];
            }
        }

        return array_values(array_unique($tabIds));
    }

    /**
     * Gewählte Bereiche prüfen; entspricht die Auswahl dem Standard, wird null gespeichert (bleibt dynamisch).
     *
     * @param array<int, mixed>|null $keys
     * @return list<string>|null
     * @throws ValidationException
     */
    public function normalizeVisibleIn(?array $keys, ?int $tabId): ?array
    {
        if ($keys === null) {
            return null;
        }

        $keys = array_values(array_unique(array_map('strval', $keys)));
        $known = $this->placements()->pluck('key')->all();
        foreach ($keys as $key) {
            if (!in_array($key, $known, true)) {
                throw ValidationException::withMessages(['visible_in' => __('validation.in', ['attribute' => 'visible_in'])]);
            }
        }

        $default = $this->defaultKeysForTab($tabId);
        sort($default);
        $sorted = $keys;
        sort($sorted);

        return $sorted === $default ? null : $keys;
    }

    /**
     * Dateien, die in einem Bereich erscheinen (Altbestand über die Tab-Auswahl, sonst ausdrücklich gewählt).
     *
     * @param list<int> $scope bereits auf sichtbare Tabs eingeschränkte Tab-Auswahl des Bereichs
     */
    public function constrainToPlacement(Builder|Relation $query, string $key, array $scope): void
    {
        $query->where(function (Builder $query) use ($key, $scope): void {
            $query->where(function (Builder $legacy) use ($scope): void {
                $legacy->whereNull('visible_in')->whereIn('tab_id', $scope === [] ? [0] : $scope);
            })->orWhereJsonContains('visible_in', $key);
        });
    }

    /**
     * Bereichs-Grenzen beim Hochladen (zusätzlich zu den globalen Grenzen in HandlesFileUpload).
     *
     * @throws ValidationException
     */
    public function assertUploadAllowed(Project $project, string $key, UploadedFile $file): void
    {
        $settings = $this->find($key)['settings'] ?? $this->normalizeSettings(null);

        if ($settings['allowed_extensions'] !== []) {
            $allowedMimeTypes = $this->mimeTypesFor($settings['allowed_extensions']);
            $extension = mb_strtolower($file->getClientOriginalExtension());
            if (
                !in_array($extension, $settings['allowed_extensions'], true) ||
                !in_array($file->getMimeType(), $allowedMimeTypes, true)
            ) {
                throw ValidationException::withMessages([
                    'file' => __('Only these file types are allowed here: :types', [
                        'types' => implode(', ', $settings['allowed_extensions']),
                    ]),
                ]);
            }
        }

        if ($settings['max_file_size_mb'] !== null && $file->getSize() > $settings['max_file_size_mb'] * 1048576) {
            throw ValidationException::withMessages([
                'file' => __('validation.file_upload.max_size', ['size' => $settings['max_file_size_mb']]),
            ]);
        }

        if ($settings['max_files'] !== null) {
            $uploaded = ProjectFile::query()
                ->where('project_id', $project->id)
                ->where('uploaded_via', $key)
                ->count();
            if ($uploaded >= $settings['max_files']) {
                throw ValidationException::withMessages([
                    'file' => __('At most :count file(s) can be uploaded here.', ['count' => $settings['max_files']]),
                ]);
            }
        }
    }

    /**
     * Einstellungen prüfen und auf die globalen Freigaben begrenzen.
     *
     * @param array<string, mixed>|null $settings
     * @return array{allowed_extensions: list<string>, max_file_size_mb: int|null, max_files: int|null}
     */
    public function normalizeSettings(?array $settings): array
    {
        $global = $this->globalLimits();

        $extensions = array_values(array_unique(array_filter(array_map(
            static fn ($extension): string => mb_strtolower(trim((string) $extension)),
            (array) ($settings['allowed_extensions'] ?? [])
        ))));
        $extensions = array_values(array_intersect($extensions, $global['extensions']));

        $maxSize = isset($settings['max_file_size_mb']) && is_numeric($settings['max_file_size_mb'])
            ? max(1, (int) $settings['max_file_size_mb'])
            : null;
        if ($maxSize !== null && $global['max_file_size_mb'] > 0) {
            $maxSize = min($maxSize, $global['max_file_size_mb']);
        }

        $maxFiles = isset($settings['max_files']) && is_numeric($settings['max_files'])
            ? max(1, (int) $settings['max_files'])
            : null;

        return [
            'allowed_extensions' => $extensions,
            'max_file_size_mb' => $maxSize,
            'max_files' => $maxFiles,
        ];
    }

    /**
     * Global für Projektdateien freigegebene Endungen ("*" = alle der Auswahlliste) und Maximalgröße.
     *
     * @return array{extensions: list<string>, max_file_size_mb: int}
     */
    public function globalLimits(): array
    {
        $settings = $this->generalSettingsService->getAllowedProjectFileMimeTypes();
        $allowed = (array) ($settings['mime_types'] ?? []);
        $all = array_keys(array_merge(MimeTypeList::MIME_TYPES, MimeTypeList::IMAGE_MIME_TYPES));

        return [
            'extensions' => array_values(in_array('*', $allowed, true)
                ? $all
                : array_intersect(array_map('strval', $allowed), $all)),
            'max_file_size_mb' => (int) ($settings['file_size'] ?? 0),
        ];
    }

    /**
     * @param list<string> $extensions
     * @return list<string>
     */
    private function mimeTypesFor(array $extensions): array
    {
        $all = array_merge(MimeTypeList::MIME_TYPES, MimeTypeList::IMAGE_MIME_TYPES);

        return array_values(array_unique(array_filter(array_map(
            static fn (string $extension): ?string => $all[$extension] ?? null,
            $extensions
        ))));
    }
}
