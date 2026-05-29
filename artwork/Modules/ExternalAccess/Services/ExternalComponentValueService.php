<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotExternallyWritableException;
use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotInTabException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Database\DatabaseManager;

class ExternalComponentValueService
{
    public function __construct(
        private readonly ExternalScopeResolver $scopeResolver,
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
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

        $value = $this->db->transaction(function () use ($external, $project, $component, $data) {
            $previousValue = ProjectComponentValue::query()
                ->where('project_id', $project->id)
                ->where('component_id', $component->id)
                ->lockForUpdate()
                ->first();

            $oldData = $previousValue?->data;
            $newData = $this->normalizeData($data);

            if ($previousValue === null) {
                $value = ProjectComponentValue::create([
                    'project_id' => $project->id,
                    'component_id' => $component->id,
                    'data' => $newData,
                ]);
            } else {
                $previousValue->update(['data' => $newData]);
                $value = $previousValue;
            }

            $this->logExternalEdit(
                external: $external,
                project: $project,
                component: $component,
                value: $value,
                oldData: $oldData,
                newData: $newData,
            );

            $this->broadcastUpdate($value, $project);

            return $value;
        });

        // Notify inviter + project managers AFTER commit (mail/queue failure must not roll back).
        $this->notificationSender->notifyTabComponentUpdated($external, $project, $tab, $component);

        return $value;
    }

    /**
     * Replicates the legacy normalization from the internal
     * ProjectComponentValueController: when a 'text' key is present, apply nl2br.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeData(array $data): array
    {
        if (array_key_exists('text', $data)) {
            return ['text' => nl2br((string) $data['text'])];
        }

        return $data;
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

    private function broadcastUpdate(ProjectComponentValue $value, Project $project): void
    {
        // Re-use the existing event so internal users viewing the same tab receive
        // the update in parallel. Channel auth (routes/channels.php) uses the web
        // guard, so external users cannot subscribe to these channels themselves.
        broadcast(new UpdateProjectComponentData($value, $project->id));
    }
}
