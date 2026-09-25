<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectComponentCrmContactService;
use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Illuminate\Database\DatabaseManager;
use Spatie\Activitylog\Models\Activity;

/**
 * "Eingegebene Daten absenden" im freigegebenen Tab: Feldwerte werden weiterhin bei jeder Eingabe
 * direkt gespeichert (eine Wahrheit, intern sofort sichtbar), die Benachrichtigung an
 * Einladende/Projektleitung geht aber erst mit diesem expliziten Absenden raus. Danach ist der Tab
 * für die externe Person gesperrt, bis intern bestätigt oder zur Überarbeitung zurückgegeben wird
 * (ExternalTabReviewService). Absende-Zeitpunkt und Status stehen am Scope und im Projektverlauf.
 */
class ExternalTabSubmissionService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
        private readonly ProjectComponentCrmContactService $crmContactService,
    ) {
    }

    public function submit(
        ExternalAccess $external,
        Project $project,
        ProjectTab $tab,
        ExternalAccessScope $scope,
    ): ExternalAccessScope {
        $changedComponents = $this->countChangedComponentsSinceLastSubmission($external, $project, $scope);

        $this->db->transaction(function () use ($external, $project, $tab, $scope, $changedComponents): void {
            $scope->forceFill([
                'last_submitted_at' => now(),
                'submission_status' => ExternalTabSubmissionStatus::SUBMITTED,
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
            ])->save();

            // Audit-Log am Scope (Verwaltungsseite)
            activity('external_tab_submission')
                ->performedOn($scope)
                ->causedBy($external)
                ->withProperties([
                    'project_id' => $project->id,
                    'project_tab_id' => $tab->id,
                    'changed_components' => $changedComponents,
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ])
                ->log('tab_submitted');

            // Projektverlauf (gleiche Property-Form wie ChangeBuilder, damit ProjectHistoryComponent
            // den Eintrag rendert; Verursacher ist der externe Zugang, nicht ein User)
            $name = $external->displayName();
            Activity::query()->create([
                'log_name' => 'project',
                'description' => 'External person submitted data in tab',
                'subject_type' => $project->getMorphClass(),
                'subject_id' => $project->id,
                'event' => 'updated',
                'causer_type' => $external->getMorphClass(),
                'causer_id' => $external->id,
                'properties' => [[
                    'type' => 'project',
                    'translationKey' => 'External person {0} submitted data in tab {1}',
                    'translationKeyPlaceholderValues' => [$name, $tab->name],
                ]],
            ]);
        });

        $this->notificationSender->notifyTabSubmitted(
            $external,
            $project,
            $tab,
            $changedComponents,
            $this->crmContactService->countCreatedByExternal($project, $external),
        );

        return $scope->refresh();
    }

    /**
     * Anzahl der Komponenten, die diese externe Person seit dem letzten Absenden (oder überhaupt)
     * in diesem Projekt geändert hat — Grundlage für die Sammelbenachrichtigung.
     */
    private function countChangedComponentsSinceLastSubmission(
        ExternalAccess $external,
        Project $project,
        ExternalAccessScope $scope,
    ): int {
        $query = Activity::query()
            ->where('log_name', 'external_component_edit')
            ->where('subject_type', (new ProjectComponentValue())->getMorphClass())
            ->where('causer_type', $external->getMorphClass())
            ->where('causer_id', $external->id)
            ->where('properties->project_id', $project->id);

        if ($scope->last_submitted_at !== null) {
            $query->where('created_at', '>', $scope->last_submitted_at);
        }

        return (int) $query->distinct()->count('subject_id');
    }
}
