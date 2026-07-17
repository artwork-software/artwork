<?php

namespace Artwork\Modules\Project\Observers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectDayAssignmentService;

/**
 * Hält Projektzuordnungen mit dem Projektzeitraum synchron: Termin-Anlage,
 * -Verschiebung, -Löschung und -Restore können den Zeitraum ändern →
 * Ganz-Zeitraum-Gruppen werden re-materialisiert, Einzeltag-Einträge außerhalb
 * aufgelöst (+ Planer-Notification). Zentral als Observer statt in jedem der
 * vielen Event-Mutations-Pfade (store/update/destroy/Serien-Loops/Bulk).
 */
class ProjectDayAssignmentEventObserver
{
    public function created(Event $event): void
    {
        $this->rematerializeProject($event->project_id);
    }

    public function updated(Event $event): void
    {
        if (!$event->wasChanged(['start_time', 'end_time', 'project_id'])) {
            return;
        }

        $this->rematerializeProject($event->project_id);

        // Projektwechsel: auch der Zeitraum des alten Projekts ändert sich
        if ($event->wasChanged('project_id')) {
            $this->rematerializeProject($event->getOriginal('project_id'));
        }
    }

    public function deleted(Event $event): void
    {
        $this->rematerializeProject($event->project_id);
    }

    public function restored(Event $event): void
    {
        $this->rematerializeProject($event->project_id);
    }

    private function rematerializeProject(int|string|null $projectId): void
    {
        if (!$projectId) {
            return;
        }

        // find() statt Relation: in Lösch-Kaskaden kann das Projekt bereits
        // soft-deleted sein → dann bewusst nichts tun (Zuordnungen werden über
        // whereHas('project') ohnehin ausgeblendet)
        $project = Project::find($projectId);

        if ($project === null) {
            return;
        }

        app(ProjectDayAssignmentService::class)->rematerializeForProjectPeriodChange($project);
    }
}
