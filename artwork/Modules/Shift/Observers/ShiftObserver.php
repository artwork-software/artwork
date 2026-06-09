<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\Shift;
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
    }

    public function deleting(Shift $shift): void
    {
        // Vor dem Löschen die zugewiesenen Mitarbeiter erfassen – danach sind die
        // shift_workers-Pivots per DB-Cascade weg. tapActivity() schreibt die Namen
        // beim 'deleted'-Event in den Log-Eintrag.
        $shift->captureDeletionAffectedWorkers();
    }

    public function deleted(Shift $shift): void
    {
        $this->recorder->record($shift, 'deleted');
    }
}
