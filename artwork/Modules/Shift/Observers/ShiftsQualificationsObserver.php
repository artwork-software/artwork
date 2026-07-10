<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Spatie\Activitylog\Contracts\Activity;

class ShiftsQualificationsObserver
{
    public function created(ShiftsQualifications $model): void
    {
        app(ShiftChangeRecorder::class)->record($model, 'created');

        $shift = $model->shift;
        if ($shift === null || $this->shiftWasJustCreated($shift)) {
            return;
        }

        // Nachträglich angelegter Schichtplatz auf einer bestehenden Schicht.
        // Implizite Überbuchungsplätze (value 0, nur overbooked_value) als
        // Überbuchungs-Änderung ausweisen — das ist die fachliche Aussage.
        if ((int) $model->value === 0 && (int) $model->overbooked_value > 0) {
            $this->logQualificationActivity(
                $shift,
                $model,
                'Overbooked slots for {0} changed from {1} to {2}',
                [$this->qualificationName($model), 0, (int) $model->overbooked_value]
            );

            return;
        }

        $this->logQualificationActivity(
            $shift,
            $model,
            'Shift slot {0} added with {1} required workers',
            [$this->qualificationName($model), (int) $model->value]
        );
    }

    public function updated(ShiftsQualifications $model): void
    {
        app(ShiftChangeRecorder::class)->record($model, 'updated');

        $shift = $model->shift;
        if ($shift === null) {
            return;
        }

        if ($model->wasChanged('value')) {
            $this->logQualificationActivity(
                $shift,
                $model,
                'Required workers for {0} changed from {1} to {2}',
                [
                    $this->qualificationName($model),
                    (int) $model->getOriginal('value'),
                    (int) $model->value,
                ]
            );
        }

        if ($model->wasChanged('overbooked_value')) {
            $this->logQualificationActivity(
                $shift,
                $model,
                'Overbooked slots for {0} changed from {1} to {2}',
                [
                    $this->qualificationName($model),
                    (int) $model->getOriginal('overbooked_value'),
                    (int) $model->overbooked_value,
                ]
            );
        }
    }

    public function deleted(ShiftsQualifications $model): void
    {
        app(ShiftChangeRecorder::class)->record($model, 'deleted');

        // Kaskade (Schicht wird gelöscht): die Schicht bekommt ihren eigenen
        // Lösch-Eintrag, einzelne Schichtplatz-Einträge wären nur Rauschen.
        if ($model->deletingViaShiftCascade) {
            return;
        }

        $shift = $model->shift;
        if ($shift === null || $shift->trashed()) {
            return;
        }

        $this->logQualificationActivity(
            $shift,
            $model,
            'Shift slot {0} removed',
            [$this->qualificationName($model)]
        );
    }

    /**
     * Beim Anlegen einer Schicht werden die initialen Schichtplätze im selben
     * Request mit erzeugt — die stehen bereits im "Schicht erstellt"-Eintrag
     * bzw. sind Teil des Anfangszustands und sollen den Verlauf nicht fluten.
     * wasRecentlyCreated greift hier nicht (die shift-Relation lädt eine frische
     * Instanz aus der DB), daher die Erstellungszeit als Signal.
     */
    private function shiftWasJustCreated(Shift $shift): bool
    {
        return $shift->wasRecentlyCreated
            || ($shift->created_at !== null && $shift->created_at->gt(now()->subSeconds(10)));
    }

    private function qualificationName(ShiftsQualifications $model): string
    {
        return $model->shiftQualification?->name ?? __('Unknown qualification');
    }

    /**
     * Schreibt den Eintrag in den Schichtverlauf (Spatie shift-Log). Manuelle
     * activity()-Einträge lösen Shift::tapActivity() nicht aus, daher werden
     * Kontext-/Snapshot-Felder hier gesetzt (Chip-Anzeige, Craft-Filter,
     * Lesbarkeit nach dem Löschen der Schicht).
     */
    private function logQualificationActivity(
        Shift $shift,
        ShiftsQualifications $model,
        string $translationKey,
        array $placeholderValues
    ): void {
        activity('shift')
            ->performedOn($shift)
            ->causedBy(auth()->user())
            ->event('updated')
            ->tap(function (Activity $activity) use ($shift, $model, $translationKey, $placeholderValues): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => $translationKey,
                    'translation_key_placeholder_values' => $placeholderValues,
                    'context' => $shift->is_committed
                        ? 'post_commit'
                        : ($shift->in_workflow ? 'in_workflow' : 'normal'),
                    'shift_id' => $shift->id,
                    'craft_id' => $shift->craft_id,
                    'shift_qualification_id' => $model->shift_qualification_id,
                    'shift_snapshot' => $shift->toActivitySnapshot(),
                ]);
            })
            ->log('Shift slot requirements changed');
    }
}
