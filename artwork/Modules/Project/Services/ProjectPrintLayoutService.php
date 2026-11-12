<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Repositories\ProjectPrintLayoutRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

readonly class ProjectPrintLayoutService
{
    public const CACHE_KEY_COMPONENTS_NOT_SPECIAL = 'print_layout_components_not_special_v2';

    public const CACHE_KEY_COMPONENTS_SPECIAL = 'print_layout_components_special_v2';

    public const CACHE_KEY_ALL_COMPONENTS = 'print_layout_all_components_v2';

    public function __construct(private ProjectPrintLayoutRepository $projectPrintLayoutRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->projectPrintLayoutRepository->getAll();
    }

    public function getProjectPrintLayouts(): Collection
    {
        return $this->projectPrintLayoutRepository->getProjectPrintLayouts();
    }

    public function storeProjectPrintLayout(array $data): void
    {
        $data['order'] = $this->projectPrintLayoutRepository->getMaxOrder() + 1;
        $data['user_id'] = auth()->id();
        $this->projectPrintLayoutRepository->createProjectPrintLayout($data);
    }

    /**
     * Leert die Bausteinlisten der Drucklayout-Palette. Nach jedem Anlegen, Ändern oder Löschen eines
     * Bausteins aufrufen, sonst zeigt die Palette bis zum Ablauf des Caches den alten Stand.
     */
    public static function clearComponentCaches(): void
    {
        Cache::forget(self::CACHE_KEY_COMPONENTS_NOT_SPECIAL);
        Cache::forget(self::CACHE_KEY_COMPONENTS_SPECIAL);
        Cache::forget(self::CACHE_KEY_ALL_COMPONENTS);
    }
}
