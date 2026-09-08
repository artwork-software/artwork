<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ComponentTreeProjector;

/**
 * External audience of the shared component tree: component types that are
 * not externally readable are filtered out; internal visibility settings
 * (User/Department) are deliberately ignored — an external link shares the
 * tab as its owner configured it.
 */
class ExternalTabComponentResolver
{
    public function __construct(private readonly ComponentTreeProjector $projector)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveTabComponents(Project $project, ProjectTab $tab): array
    {
        return $this->projector->project(
            $tab,
            [
                'component.projectValue' => fn ($q) => $q->where('project_id', $project->id),
                'disclosureComponents.component.projectValue' => fn ($q) => $q->where('project_id', $project->id),
            ],
            fn (Component $component): bool => $this->isReadable($component),
            fn (
                Component $component,
                array $scope,
                ComponentInTab|DisclosureComponents $context,
            ): array => $this->serializeComponent($component, $context),
        );
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
    private function serializeComponent(
        Component $component,
        ComponentInTab|DisclosureComponents $context,
    ): array {
        return [
            ...($context instanceof ComponentInTab ? ['component_in_tab_id' => $context->id] : []),
            'component_id' => $component->id,
            'type' => $component->type,
            'name' => $component->name,
            'data_schema' => $component->data,
            'value' => $component->projectValue?->data,
            'is_writable' => $this->isWritable($component),
        ];
    }
}
