<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftObserver
{
    public function __construct(
        protected ShiftChangeRecorder $recorder
    ) {}

    public function created(Shift $shift): void
    {
        $this->recorder->record($shift, 'created');
    }

    public function updated(Shift $shift): void
    {
        $this->recorder->record($shift, 'updated');

        // Datum/Uhrzeit geändert → abgegebene Zu-/Absagen beziehen sich auf die
        // alte Zeit und werden auf "ausstehend" zurückgesetzt (bewusst ohne
        // Benachrichtigung — die Personen sehen den offenen Status im Einsatzplan).
        if ($shift->wasChanged(['start_date', 'end_date', 'start', 'end'])) {
            ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->whereNotNull('confirmation_status')
                ->update([
                    'confirmation_status' => null,
                    'confirmation_at' => null,
                    'confirmation_by_user_id' => null,
                    'confirmation_comment' => null,
                ]);
        }
    }

    public function deleting(Shift $shift): void
    {
        // Vor dem Löschen die zugewiesenen Mitarbeiter erfassen – danach sind die
        // shift_workers-Pivots per DB-Cascade weg. tapActivity() schreibt die Namen
        // beim 'deleted'-Event in den Log-Eintrag. In Kaskaden (ShiftService::delete)
        // sind die Pivots hier bereits soft-deleted — dort wurde vorab erfasst,
        // die Namen dürfen nicht mit einer leeren Liste überschrieben werden.
        if ($shift->deletionAffectedWorkers === null) {
            $shift->captureDeletionAffectedWorkers();
        }
    }

    public function deleted(Shift $shift): void
    {
        $this->recorder->record($shift, 'deleted');
    }
}
