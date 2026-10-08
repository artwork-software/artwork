<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

/**
 * The one write path for project component values — web, external and app
 * all go through here. The value is checked against the component type
 * (ProjectComponentValueNormalizer); only a real change broadcasts the
 * live-update event to internal viewers of the tab, after the commit.
 */
readonly class ProjectComponentValueService
{
    public function __construct(
        private DatabaseManager $db,
        private ProjectComponentValueNormalizer $normalizer,
    ) {
    }

    /**
     * @param array<string, mixed>|null $data
     * @param Closure(ProjectComponentValue, array<string, mixed>|null): void|null $inTransaction
     *        Runs inside the write transaction with the saved value and the
     *        previous data — for callers that audit-log the change atomically.
     * @param bool $toOthers skip the requesting socket; it takes the value from the response
     * @throws ValidationException
     */
    public function updateValue(
        Project $project,
        Component $component,
        ?array $data,
        ?Closure $inTransaction = null,
        bool $toOthers = false,
    ): ProjectComponentValue {
        $newData = $this->normalizer->normalize($component, $data);
        $changed = false;

        $value = $this->db->transaction(function () use ($project, $component, $newData, $inTransaction, &$changed) {
            $previousValue = ProjectComponentValue::query()
                ->where('project_id', $project->id)
                ->where('component_id', $component->id)
                ->lockForUpdate()
                ->first();

            $oldData = $previousValue?->data;

            // Unique index (project_id, component_id): parallel autosaves never create a second row.
            $value = ProjectComponentValue::query()->updateOrCreate(
                ['project_id' => $project->id, 'component_id' => $component->id],
                ['data' => $newData],
            );
            $changed = $value->wasRecentlyCreated || $value->wasChanged('data');

            if ($inTransaction !== null) {
                $inTransaction($value, $oldData);
            }

            return $value;
        });

        // A focus change without an edit would otherwise make every viewer reload the value.
        if ($changed) {
            SafeBroadcast::send(new UpdateProjectComponentData($value, $project->id), toOthers: $toOthers);
        }

        return $value;
    }
}
