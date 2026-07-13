<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageBookingLog;
use Artwork\Modules\Budget\Models\SageBookingLogEntry;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Illuminate\Support\Carbon;

/**
 * Records which Sage booking records are created/updated/deleted during an import or delete run.
 *
 * Registered as a singleton so that the two data services (SageAssignedDataService,
 * SageNotAssignedDataService), Sage100Service and SageBookingDataDeleteService all share the
 * same recording context. Outside of an active run record() is a no-op, so manual moves,
 * single deletes and drag/drop operations are never logged.
 */
class SageBookingLogRecorder
{
    private const array ACTIONS_IMPORT = ['created', 'updated'];

    private const array ACTIONS_DELETE = ['deleted'];

    private bool $recording = false;

    private ?int $logId = null;

    /** @var array<int, string> */
    private array $trackedActions = [];

    /**
     * Start a new run. Creates the log header immediately (outside of any per-booking
     * transaction) so entries can be inserted incrementally and survive/roll back together
     * with their booking transaction.
     *
     * @param array<string, mixed> $criteria
     */
    public function startRun(string $type, array $criteria, ?int $userId, ?string $source = null): void
    {
        // Guard against accidental nesting: an already-running recorder keeps its context.
        if ($this->recording) {
            return;
        }

        $log = SageBookingLog::create([
            'type' => $type,
            'source' => $source,
            'user_id' => $userId,
            'criteria' => array_filter($criteria, static fn ($value): bool => $value !== null),
            'started_at' => Carbon::now(),
        ]);

        $this->logId = $log->id;
        $this->trackedActions = $type === 'delete' ? self::ACTIONS_DELETE : self::ACTIONS_IMPORT;
        $this->recording = true;
    }

    public function record(string $action, SageAssignedData|SageNotAssignedData $model): void
    {
        if (!$this->recording || $this->logId === null || !in_array($action, $this->trackedActions, true)) {
            return;
        }

        $isAssigned = $model instanceof SageAssignedData;

        // Logging must never break the actual import/delete. record() runs inside the caller's
        // per-booking transaction, so a throwing entry insert would roll the real booking back.
        // Swallow and report any logging failure instead.
        try {
            SageBookingLogEntry::create([
                'sage_booking_log_id' => $this->logId,
                'action' => $action,
                'target_type' => $isAssigned ? 'assigned' : 'not_assigned',
                'sage_record_id' => $model->getKey(),
                'sage_id' => $model->sage_id,
                'tan' => $model->tan,
                'periode' => $model->periode,
                'kto_haben' => $model->kto_haben,
                'kreditor' => $model->kreditor,
                'buchungstext' => $model->buchungstext,
                'buchungsbetrag' => $model->buchungsbetrag,
                'belegnummer' => $model->belegnummer,
                'belegdatum' => $model->belegdatum,
                'kto_soll' => $model->kto_soll,
                'sa_kto' => $model->sa_kto,
                'kst_traeger' => $model->kst_traeger,
                'kst_stelle' => $model->kst_stelle,
                'buchungsdatum' => $model->buchungsdatum,
                'is_collective_booking' => (bool) $model->is_collective_booking,
                'project_id' => $isAssigned ? null : $model->project_id,
                'column_cell_id' => $isAssigned ? $model->column_cell_id : null,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Finish the current run: recompute the per-action counts from the persisted entries and
     * stamp finished_at. Never throws — logging must not break the import/delete itself.
     */
    public function finishRun(): void
    {
        if (!$this->recording || $this->logId === null) {
            $this->reset();
            return;
        }

        try {
            $log = SageBookingLog::find($this->logId);
            if ($log !== null) {
                $counts = SageBookingLogEntry::query()
                    ->where('sage_booking_log_id', $this->logId)
                    ->selectRaw('action, COUNT(*) as aggregate')
                    ->groupBy('action')
                    ->pluck('aggregate', 'action');

                $log->update([
                    'created_count' => (int) ($counts['created'] ?? 0),
                    'updated_count' => (int) ($counts['updated'] ?? 0),
                    'deleted_count' => (int) ($counts['deleted'] ?? 0),
                    'finished_at' => Carbon::now(),
                ]);
            }
        } finally {
            $this->reset();
        }
    }

    public function isRecording(): bool
    {
        return $this->recording;
    }

    private function reset(): void
    {
        $this->recording = false;
        $this->logId = null;
        $this->trackedActions = [];
    }
}
