<?php

namespace Artwork\Modules\Shift\Events\Concerns;

use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Shift\Models\Shift;

/**
 * Gemeinsamer Lookup-Anhang für die Einzel-Schicht-Broadcasts (anlegen/bearbeiten).
 *
 * Der Dienstplan kennt Projekte/Gewerke/Schichtgruppen nur über seine Lookup-Maps aus dem
 * initialen Load. Wechselt eine Schicht auf ein Projekt, das im geladenen Zeitraum noch keine
 * Schicht hatte, fehlt der Eintrag – die Schicht erschien bis zum Neuladen als "ohne Projekt".
 * Analog zu MultiShiftCreateInShiftPlan werden die Einträge deshalb mitgeschickt.
 */
trait BuildsShiftBroadcastLookups
{
    /**
     * Lädt die Relationen für den Broadcast. `project` wird bewusst neu geladen (nicht loadMissing):
     * Die Relation kann vor dem Wechsel der project_id bereits (mit dem alten Projekt) geladen worden sein.
     */
    protected function loadBroadcastRelations(Shift $shift): void
    {
        $shift->loadMissing([
            'craft:id,name,abbreviation,color',
            'shiftsQualifications',
            'globalQualifications',
            'users.globalQualifications',
            'freelancer.globalQualifications',
            'serviceProvider.globalQualifications',
            'shiftGroup:id,name',
        ]);

        $shift->unsetRelation('project');
        $shift->load([
            'project.status:id,name,color',
            'project.groups:id,name,state,artists,is_group,icon,color',
            'project.groups.status:id,name,color',
        ]);
    }

    /**
     * @return array{projectsById: array<int, array<string, mixed>>, craftsById: array<int, array<string, mixed>>, shiftGroupsById: array<int, array{id: int, name: string}>}
     */
    protected function buildBroadcastLookups(Shift $shift): array
    {
        $projectsById = [];
        $craftsById = [];
        $shiftGroupsById = [];

        if ($shift->project) {
            $projectsById[$shift->project->id] = ShiftCalendarService::buildProjectLookupEntry($shift->project);
        }
        if ($shift->craft) {
            $craftsById[$shift->craft->id] = ShiftCalendarService::buildCraftLookupEntry($shift->craft);
        }
        if ($shift->shiftGroup) {
            $shiftGroupsById[$shift->shiftGroup->id] = [
                'id' => $shift->shiftGroup->id,
                'name' => $shift->shiftGroup->name,
            ];
        }

        return [
            'projectsById' => $projectsById,
            'craftsById' => $craftsById,
            'shiftGroupsById' => $shiftGroupsById,
        ];
    }
}
