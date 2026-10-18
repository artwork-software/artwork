<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\ProjectTab;
use Closure;

/**
 * Walks a tab's component tree (components in display order, including the
 * children of disclosure components) exactly once for every read surface.
 * What an audience (external, app, …) may see and how a component is
 * serialized is injected — the projector owns only the tree structure and
 * the structural keys `note` and `children`.
 */
final readonly class ComponentTreeProjector
{
    /**
     * @param array<int|string, mixed> $eagerLoads component relations to eager-load
     * @param Closure(Component): bool $sees
     * @param Closure(Component, array<int, int>, ComponentInTab|DisclosureComponents): array<string, mixed> $serialize
     * @return array<int, array<string, mixed>>
     */
    public function project(ProjectTab $tab, array $eagerLoads, Closure $sees, Closure $serialize): array
    {
        return ComponentInTab::query()
            ->where('project_tab_id', $tab->id)
            ->with($eagerLoads)
            ->orderBy('order')
            ->get()
            ->filter(static fn (ComponentInTab $cit): bool => $cit->component !== null && $sees($cit->component))
            ->values()
            ->map(static fn (ComponentInTab $cit): array => [
                ...$serialize($cit->component, (array) ($cit->scope ?? []), $cit),
                'note' => $cit->note,
                'children' => $cit->disclosureComponents
                    ->filter(
                        static fn (DisclosureComponents $dc): bool => $dc->component !== null && $sees($dc->component),
                    )
                    ->values()
                    ->map(static fn (DisclosureComponents $dc): array => $serialize($dc->component, [], $dc))
                    ->all(),
            ])
            ->all();
    }
}
