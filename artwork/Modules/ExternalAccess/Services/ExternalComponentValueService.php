<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotExternallyWritableException;
use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotInTabException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectComponentValueService;

class ExternalComponentValueService
{
    public function __construct(
        private readonly ExternalScopeResolver $scopeResolver,
        private readonly ProjectComponentValueService $componentValueService,
    ) {
    }

    public function updateComponentValue(
        ExternalAccess $external,
        Project $project,
        ProjectTab $tab,
        Component $component,
        array $data,
    ): ProjectComponentValue {
        // Defense in depth — the middleware checked the scope, but the component
        // type must be externally writable as well.
        $type = ProjectTabComponentEnum::tryFrom((string) $component->type);
        if (!$type || !$type->isExternallyWritable()) {
            throw new ComponentNotExternallyWritableException($component);
        }

        // Defense in depth — the component must actually live in the shared tab
        // (prevents cross-tab manipulation).
        if (!$this->scopeResolver->componentBelongsToTab($component->id, $tab->id)) {
            throw new ComponentNotInTabException($component, $tab);
        }

        $value = $this->componentValueService->updateValue(
            $project,
            $component,
            $data,
            fn (ProjectComponentValue $value, ?array $oldData) => $this->logExternalEdit(
                external: $external,
                project: $project,
                component: $component,
                value: $value,
                oldData: $oldData,
                newData: $value->data,
            ),
        );

        // Keine Benachrichtigung pro Feld: Einladende werden erst beim expliziten
        // "Daten absenden" (ExternalTabSubmissionService) gesammelt informiert.
        return $value;
    }

    private function logExternalEdit(
        ExternalAccess $external,
        Project $project,
        Component $component,
        ProjectComponentValue $value,
        ?array $oldData,
        array $newData,
    ): void {
        activity('external_component_edit')
            ->performedOn($value)
            ->causedBy($external)
            ->withProperties([
                'project_id' => $project->id,
                'component_id' => $component->id,
                'component_type' => $component->type,
                'old_value' => $oldData,
                'new_value' => $newData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('component_value_updated');
    }
}
