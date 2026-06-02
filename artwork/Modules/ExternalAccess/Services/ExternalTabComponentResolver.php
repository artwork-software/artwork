<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;

class ExternalTabComponentResolver
{
    /**
     * Returns the components of a tab in a structure suitable for the external
     * view. Component types that are not externally readable are filtered out;
     * internal visibility settings (User/Department) are deliberately ignored.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveTabComponents(Project $project, ProjectTab $tab): array
    {
        $componentsInTab = ComponentInTab::query()
            ->where('project_tab_id', $tab->id)
            ->with([
                'component.projectValue' => fn ($q) => $q->where('project_id', $project->id),
                'disclosureComponents.component.projectValue' => fn ($q) => $q->where('project_id', $project->id),
            ])
            ->orderBy('order')
            ->get();

        return $componentsInTab
            ->filter(fn (ComponentInTab $cit) => $cit->component && $this->isReadable($cit->component))
            ->values()
            ->map(fn (ComponentInTab $cit) => $this->serializeComponentInTab($cit))
            ->toArray();
    }

    private function isReadable(Component $component): bool
    {
        $type = ProjectTabComponentEnum::tryFrom((string) $component->type);

        return $type?->isExternallyReadable() ?? false;
    }

    private function isWritable(Component $component): bool
    {
        $type = ProjectTabComponentEnum::tryFrom((string) $component->type);

        return $type?->isExternallyWritable() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComponentInTab(ComponentInTab $cit): array
    {
        $component = $cit->component;

        return [
            'component_in_tab_id' => $cit->id,
            'component_id' => $component->id,
            'type' => $component->type,
            'name' => $component->name,
            'data_schema' => $component->data,
            'value' => $component->projectValue?->data,
            'is_writable' => $this->isWritable($component),
            'note' => $cit->note,
            // Disclosure components contain sub-components — resolved recursively,
            // each child filtered by isExternallyReadable as well.
            'children' => $cit->disclosureComponents
                ->filter(fn (DisclosureComponents $dc) => $dc->component && $this->isReadable($dc->component))
                ->values()
                ->map(fn (DisclosureComponents $dc) => $this->serializeDisclosureChild($dc))
                ->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDisclosureChild(DisclosureComponents $dc): array
    {
        $component = $dc->component;

        return [
            'component_id' => $component->id,
            'type' => $component->type,
            'name' => $component->name,
            'data_schema' => $component->data,
            'value' => $component->projectValue?->data,
            'is_writable' => $this->isWritable($component),
        ];
    }
}
