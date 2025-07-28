<?php

namespace Artwork\Modules\Workflow\Jobs;

use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NightlyShiftRuleValidationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $daysAhead = 14, protected bool $autoCreateWorkflows = true)
    {
    }

    public function handle(WorkflowRuleService $workflowRuleService): void
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($this->daysAhead);

        Log::info('Starting nightly shift rule validation', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_ahead' => $this->daysAhead
        ]);

        try {
            // Lösche alte Validierungen für den Zeitraum um Duplikate zu vermeiden
            $this->cleanupOldViolations($startDate, $endDate);

            // Führe Validierung durch
            $violations = $workflowRuleService->checkRuleViolationsForDateRange($startDate, $endDate);

            $violationCount = $violations->count();
            $newViolations = 0;
            $updatedViolations = 0;

            foreach ($violations as $violation) {
                $existingViolation = WorkflowRuleViolation::where([
                    'workflow_rule_id' => $violation->workflow_rule_id,
                    'subject_type' => $violation->subject_type,
                    'subject_id' => $violation->subject_id,
                    'violation_date' => $violation->violation_date
                ])->first();

                if ($existingViolation) {
                    // Aktualisiere bestehende Verletzung
                    $existingViolation->update([
                        'violation_data' => $violation->violation_data,
                        'severity' => $violation->severity,
                        'updated_at' => now()
                    ]);
                    $updatedViolations++;
                } else {
                    // Erstelle neue Verletzung
                    $violation->save();
                    $newViolations++;

                    // Erstelle Workflow falls aktiviert
                    if ($this->autoCreateWorkflows) {
                        $this->createWorkflowForViolation($violation);
                    }
                }
            }

            Log::info('Nightly shift rule validation completed', [
                'total_violations' => $violationCount,
                'new_violations' => $newViolations,
                'updated_violations' => $updatedViolations,
                'duration' => Carbon::now()->diffInSeconds($startDate) . ' seconds'
            ]);

            if ($newViolations > 0) {
                $this->sendViolationSummary($newViolations, $startDate, $endDate);
            }
        } catch (\Exception $e) {
            Log::error('Error during nightly shift rule validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function cleanupOldViolations(Carbon $startDate, Carbon $endDate): void
    {
        // Lösche nur "pending" Verstöße, behalte validierte/bearbeitete
        WorkflowRuleViolation::whereBetween('violation_date', [$startDate, $endDate])
            ->where('status', 'pending')
            ->whereDate('created_at', '<', now()->subHour()) // Behalte frische Validierungen
            ->delete();
    }

    private function createWorkflowForViolation(WorkflowRuleViolation $violation): void
    {
        try {
            // Hier würde die Workflow-Erstellung implementiert werden,
            // falls die Workflow-Engine entsprechend konfiguriert ist

            Log::debug('Created workflow for violation', [
                'violation_id' => $violation->id,
                'rule_id' => $violation->workflow_rule_id
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create workflow for violation', [
                'violation_id' => $violation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendViolationSummary(int $newViolationCount, Carbon $startDate, Carbon $endDate): void
    {
        // Implementierung der Zusammenfassung-E-Mail
        // könnte als separater Job implementiert werden

        Log::info('Violation summary would be sent', [
            'new_violations' => $newViolationCount,
            'date_range' => $startDate->toDateString() . ' - ' . $endDate->toDateString()
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);
        Log::error('Nightly shift rule validation job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
