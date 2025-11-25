<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftPlanRequestChange;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ShiftChangeRecorder
{
    /**
     * Haupteinstiegspunkt: von Observern aus aufrufen.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $eventName created|updated|deleted
     */
    public function record(Model $model, string $eventName): void
    {
        if (! in_array($eventName, ['created', 'updated', 'deleted'], true)) {
            return;
        }

        $meta = $this->extractMeta($model);
        if ($meta === null) {
            // Nicht relevant für Workflow / Änderungsliste
            return;
        }

        // Feldänderungen ermitteln
        $fieldChanges = $this->buildFieldChanges($model, $meta['fields_to_watch'], $eventName);

        if (empty($fieldChanges)) {
            // Nichts Relevantes geändert
            return;
        }

        // Workflow-Änderungen (solange in_workflow = true && current_request_id gesetzt)
        if ($meta['in_workflow'] && $meta['current_request_id']) {
            $this->recordWorkflowChange($meta, $fieldChanges, $eventName, $model);
            return;
        }

        // Änderungen nach Festschreibung
        if ($meta['is_committed']) {
            $this->recordCommittedChange($meta, $fieldChanges, $eventName);
        }
    }

    /**
     * Meta-Informationen zum Model ermitteln (Shift, Pivot, Qualifikationen).
     *
     * @param Model $model
     * @return array<string,mixed>|null
     */
    protected function extractMeta(Model $model): ?array
    {
        // Shift direkt
        if ($model instanceof Shift) {
            return [
                'subject_type'       => Shift::class,
                'subject_id'         => $model->getKey(),
                'shift_id'           => $model->getKey(),
                'craft_id'           => $model->craft_id,
                'in_workflow'        => (bool) $model->in_workflow,
                'current_request_id' => $model->current_request_id,
                'is_committed'       => (bool) $model->is_committed,
                'affected_user_id'   => null,
                'changed_by_user_id' => Auth::id(),
                'fields_to_watch'    => [
                    'start_date',
                    'end_date',
                    'start',
                    'end',
                    'break_minutes',
                    'craft_id',
                    'description',
                    'room_id',
                    'project_id',
                    'shift_group_id',
                ],
            ];
        }

        // Pivot ShiftUser
        if ($model instanceof ShiftUser) {
            /** @var Shift|null $shift */
            $shift = $model->shift;
            if (! $shift) {
                return null;
            }

            return [
                'subject_type'       => ShiftUser::class,
                'subject_id'         => $model->getKey(),
                'shift_id'           => $model->shift_id,
                'craft_id'           => $shift->craft_id,
                'in_workflow'        => (bool) $shift->in_workflow,
                'current_request_id' => $shift->current_request_id,
                'is_committed'       => (bool) $shift->is_committed,
                'affected_user_type' => \Artwork\Modules\User\Models\User::class,
                'affected_user_id'   => $model->user_id,
                'changed_by_user_id' => Auth::id(),
                'fields_to_watch'    => [
                    'shift_id',
                    'user_id',
                    'shift_qualification_id',
                    'shift_count',
                    'craft_abbreviation',
                    'short_description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                ],
            ];
        }

        // Pivot ShiftFreelancer
        if ($model instanceof ShiftFreelancer) {
            /** @var Shift|null $shift */
            $shift = $model->shift;
            if (! $shift) {
                return null;
            }

            return [
                'subject_type'       => ShiftFreelancer::class,
                'subject_id'         => $model->getKey(),
                'shift_id'           => $model->shift_id,
                'craft_id'           => $shift->craft_id,
                'in_workflow'        => (bool) $shift->in_workflow,
                'current_request_id' => $shift->current_request_id,
                'is_committed'       => (bool) $shift->is_committed,
                'affected_user_type' => \Artwork\Modules\Freelancer\Models\Freelancer::class,
                'affected_user_id'   => $model->freelancer_id ?? null,
                'changed_by_user_id' => Auth::id(),
                'fields_to_watch'    => [
                    'shift_id',
                    'freelancer_id',
                    'shift_qualification_id',
                    'shift_count',
                    'craft_abbreviation',
                    'short_description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                ],
            ];
        }

        // Pivot ShiftServiceProvider
        if ($model instanceof ShiftServiceProvider) {
            /** @var Shift|null $shift */
            $shift = $model->shift;
            if (! $shift) {
                return null;
            }

            return [
                'subject_type'       => ShiftServiceProvider::class,
                'subject_id'         => $model->getKey(),
                'shift_id'           => $model->shift_id,
                'craft_id'           => $shift->craft_id,
                'in_workflow'        => (bool) $shift->in_workflow,
                'current_request_id' => $shift->current_request_id,
                'is_committed'       => (bool) $shift->is_committed,
                'affected_user_type' => \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class,
                'affected_user_id'   => $model->service_provider_id ?? null,
                'changed_by_user_id' => Auth::id(),
                'fields_to_watch'    => [
                    'shift_id',
                    'service_provider_id',
                    'shift_qualification_id',
                    'shift_count',
                    'craft_abbreviation',
                    'short_description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                ],
            ];
        }

        // Normale Qualifikationen (ShiftsQualifications)
        if ($model instanceof ShiftsQualifications) {
            /** @var Shift|null $shift */
            $shift = $model->shift;
            if (! $shift) {
                return null;
            }

            return [
                // WICHTIG: Subject auf den Shift mappen, nicht auf ShiftsQualifications
                'subject_type'       => Shift::class,
                'subject_id'         => $model->shift_id,
                'shift_id'           => $model->shift_id,
                'craft_id'           => $shift->craft_id,
                'in_workflow'        => (bool) $shift->in_workflow,
                'current_request_id' => $shift->current_request_id,
                'is_committed'       => (bool) $shift->is_committed,
                'affected_user_id'   => null,
                'changed_by_user_id' => Auth::id(),
                // Wir beobachten vor allem value (Menge) + evtl. die zugeordnete Quali
                'fields_to_watch'    => [
                    'value',
                    'shift_qualification_id',
                ],
            ];
        }

        return null;
    }

    /**
     * Baut das field_changes-JSON auf Basis von getDirty/getOriginal.
     *
     * → IDs werden zusätzlich in Labels aufgelöst.
     * → Qualifikations-Mengen (ShiftsQualifications) bekommen eigene Struktur.
     *
     * @param Model $model
     * @param array<int,string> $fieldsToWatch
     * @param string $eventName
     * @return array<string,mixed>
     */
    protected function buildFieldChanges(
        Model $model,
        array $fieldsToWatch,
        string $eventName,
        ?array $originalOverride = null
    ): array {
        $fieldChanges = [];

        // Lösch-Fall bleibt gleich
        if ($eventName === 'deleted') {
            $original = Arr::only($model->getOriginal(), $fieldsToWatch);

            if (! empty($original)) {
                $fieldChanges['_deleted'] = $original;
            }

            return $fieldChanges;
        }

        // created / updated
        // ⬇️ hier neu: entweder das übergebene Original oder das Model-Original
        $original = $originalOverride ?? $model->getOriginal();
        $dirty    = $model->getDirty();

        foreach ($dirty as $key => $newValue) {
            if (! in_array($key, $fieldsToWatch, true)) {
                continue;
            }

            $oldValue = $original[$key] ?? null;

            if ($oldValue == $newValue) {
                continue;
            }

            if ($model instanceof ShiftsQualifications && $key === 'value') {
                $qualificationName = optional($model->shiftQualification)->name ?? 'Unbenannte Qualifikation';

                $fieldChanges['qualifications'][] = [
                    'qualification_id' => $model?->shift_qualification_id,
                    'label'            => $qualificationName,
                    'old'              => $oldValue,
                    'new'              => $newValue,
                    'kind'             => 'normal',
                ];

                continue;
            }

            $change = [
                'old' => $oldValue,
                'new' => $newValue,
            ];

            $labels = $this->resolveLabelsForField($model, $key, $oldValue, $newValue);
            if ($labels !== null) {
                // array_merge vermeiden: direkte Zuordnung
                $change['old_label'] = $labels['old_label'] ?? null;
                $change['new_label'] = $labels['new_label'] ?? null;
            }

            $fieldChanges[$key] = $change;
        }

        return $fieldChanges;
    }

    public function recordWithOriginal(Model $model, array $original, string $eventName = 'updated'): void
    {
        if ($eventName !== 'updated') {
            return;
        }

        $meta = $this->extractMeta($model);
        if ($meta === null) {
            return;
        }

        $fieldChanges = $this->buildFieldChanges(
            model:          $model,
            fieldsToWatch:  $meta['fields_to_watch'],
            eventName:      $eventName,
            originalOverride: $original,   // ⬅️ hier nutzen wir das externe Original
        );

        if (empty($fieldChanges)) {
            return;
        }

        if ($meta['in_workflow'] && $meta['current_request_id']) {
            $this->recordWorkflowChange($meta, $fieldChanges, $eventName, $model);
            return;
        }

        if ($meta['is_committed']) {
            $this->recordCommittedChange($meta, $fieldChanges, $eventName);
        }
    }

    public function recordGlobalQualificationDiff(Shift $shift, array $before, array $after): void
    {
        $changes = [];

        $allIds = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($allIds as $id) {
            $old = (int)($before[$id] ?? 0);
            $new = (int)($after[$id] ?? 0);

            if ($old === $new) {
                continue;
            }

            $changes['global_qualifications'][] = [
                'global_qualification_id' => $id,
                'label'                   => optional(GlobalQualification::find($id))->name,
                'old'                     => $old,
                'new'                     => $new,
                'kind'                    => 'global',
            ];
        }

        if (empty($changes)) {
            return;
        }

        $meta = [
            'subject_type'       => Shift::class,
            'subject_id'         => $shift->getKey(),
            'shift_id'           => $shift->getKey(),
            'craft_id'           => $shift->craft_id,
            'in_workflow'        => (bool) $shift->in_workflow,
            'current_request_id' => $shift->current_request_id,
            'is_committed'       => (bool) $shift->is_committed,
            'affected_user_id'   => null,
            'changed_by_user_id' => Auth::id(),
        ];

        if ($meta['in_workflow'] && $meta['current_request_id']) {
            $this->recordWorkflowChange($meta, $changes, 'updated', $shift);
            return;
        }

        if ($meta['is_committed']) {
            $this->recordCommittedChange($meta, $changes, 'updated');
        }
    }

    /**
     * IDs zu sprechenden Labels auflösen.
     *
     * @param Model $model
     * @param string $field
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return array<string,string|null>|null
     */
    protected function resolveLabelsForField(Model $model, string $field, mixed $oldValue, mixed $newValue): ?array
    {
        if ($oldValue === null && $newValue === null) {
            return null;
        }

        if ($model instanceof Shift) {
            return match ($field) {
                'craft_id'           => $this->resolveRelationLabels(Craft::class, 'name', $oldValue, $newValue),
                'project_id'         => $this->resolveRelationLabels(Project::class, 'name', $oldValue, $newValue),
                'room_id'            => $this->resolveRelationLabels(Room::class, 'name', $oldValue, $newValue),
                'event_id'           => $this->resolveRelationLabels(Event::class, 'title', $oldValue, $newValue),
                'committing_user_id' => $this->resolveRelationLabels(User::class, 'display_name', $oldValue, $newValue),
                default              => null,
            };
        }

        if ($model instanceof ShiftUser) {
            return match ($field) {
                'user_id'                => $this->resolveRelationLabels(
                    User::class,
                    'display_name',
                    $oldValue,
                    $newValue
                ),
                'shift_qualification_id' => $this->resolveRelationLabels(
                    ShiftQualification::class,
                    'name',
                    $oldValue,
                    $newValue
                ),
                default                  => null,
            };
        }

        // ShiftsQualifications: Qualifikationswechsel auf Namen mappen
        if ($model instanceof ShiftsQualifications) {
            return match ($field) {
                'shift_qualification_id' => $this->resolveRelationLabels(
                    ShiftQualification::class,
                    'name',
                    $oldValue,
                    $newValue
                ),
                default => null,
            };
        }

        if ($model instanceof ShiftFreelancer) {
            // Beispiel, falls du eine Freelancer::class hast:
            // return match ($field) {
            //     'freelancer_id' => $this->resolveRelationLabels(
            //         Freelancer::class,
            //         'display_name',
            //         $oldValue,
            //         $newValue
            //     ),
            //     'shift_qualification_id' => $this->resolveRelationLabels(
            //         ShiftQualification::class,
            //         'name',
            //         $oldValue,
            //         $newValue
            //     ),
            //     default => null,
            // };
            return null;
        }

        if ($model instanceof ShiftServiceProvider) {
            // Beispiel, falls du eine ServiceProvider::class hast:
            // return match ($field) {
            //     'service_provider_id' => $this->resolveRelationLabels(
            //         ServiceProvider::class,
            //         'name',
            //         $oldValue,
            //         $newValue
            //     ),
            //     'shift_qualification_id' => $this->resolveRelationLabels(
            //         ShiftQualification::class,
            //         'name',
            //         $oldValue,
            //         $newValue
            //     ),
            //     default => null,
            // };
            return null;
        }
        return null;
    }

    /**
     * Hilfsfunktion: lädt altes und neues Modell und holt die Spalte für die Labels.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param string $labelColumn
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return array<string, string|null>
     */
    protected function resolveRelationLabels(
        string $modelClass,
        string $labelColumn,
        mixed $oldValue,
        mixed $newValue
    ): array {
        $oldLabel = null;
        $newLabel = null;

        if (! empty($oldValue)) {
            $oldLabel = optional($modelClass::find($oldValue))->{$labelColumn};
        }

        if (! empty($newValue)) {
            $newLabel = optional($modelClass::find($newValue))->{$labelColumn};
        }

        return [
            'old_label' => $oldLabel,
            'new_label' => $newLabel,
        ];
    }

    /**
     * Workflow-Änderung (solange Request pending ist).
     *
     * @param array<string,mixed> $meta
     * @param array<string,mixed> $fieldChanges
     * @param string $eventName
     * @param Model $model
     * @return void
     */
    protected function recordWorkflowChange(array $meta, array $fieldChanges, string $eventName, Model $model): void
    {
        // Falls schon ein Eintrag für diesen Request + Subject existiert,
        // speichern wir keinen _initial-Block mehr.
        $hasExistingChange = ShiftPlanRequestChange::query()
            ->where('shift_plan_request_id', $meta['current_request_id'])
            ->where('subject_type', $meta['subject_type'])
            ->where('subject_id', $meta['subject_id'])
            ->exists();

        if (! $hasExistingChange) {
            // Nur die relevanten Felder aus dem Originalzustand übernehmen
            $fieldsToWatch = $meta['fields_to_watch'] ?? [];
            $initial       = Arr::only($model->getOriginal(), $fieldsToWatch);

            // ⬇️ NEU: Datumsfelder auf lokales Y-m-d normalisieren
            $initial = $this->normalizeInitialDates($initial);

            // Shift-spezifische Zusatzinfos (Qualifikationen, Projekt, Raum, Craft)
            if ($model instanceof Shift) {
                $this->appendShiftInitialData($model, $initial);
            }

            if (! empty($initial)) {
                $fieldChanges['_initial'] = $initial;
            }
        }

        ShiftPlanRequestChange::create([
            'shift_plan_request_id' => $meta['current_request_id'],
            'subject_type'          => $meta['subject_type'],
            'subject_id'            => $meta['subject_id'],
            'change_type'           => $eventName,
            'field_changes'         => $fieldChanges,
            'affected_user_id'      => $meta['affected_user_id'] ?? null,
            'changed_by_user_id'    => $meta['changed_by_user_id'] ?? null,
            'changed_at'            => now(),
        ]);
    }

    /**
     * Ergänzt den _initial-Block um shift-spezifische Informationen.
     */
    protected function appendShiftInitialData(Shift $shift, array &$initial): void
    {
        $shift->loadMissing([
            'globalQualifications.globalQualification',
            'shiftsQualifications.shiftQualification',
            'project',
            'room',
            'craft',
        ]);

        $globalQuals = $shift->globalQualifications
            ->map(static fn ($gq) => [
                'global_qualification_id' => $gq->global_qualification_id,
                'label'                   => $gq->globalQualification?->name,
                'value'                   => $gq->value,
            ])
            ->filter()
            ->values()
            ->all();

        if (! empty($globalQuals)) {
            $initial['global_qualifications'] = $globalQuals;
        }

        $shiftQuals = $shift->shiftsQualifications
            ->map(static fn ($sq) => [
                'shift_qualification_id' => $sq->shift_qualification_id,
                'label'                  => $sq->shiftQualification?->name,
                'value'                  => $sq->value,
            ])
            ->filter()
            ->values()
            ->all();

        if (! empty($shiftQuals)) {
            $initial['shifts_qualifications'] = $shiftQuals;
        }

        if (! empty($shift->project_id)) {
            $initial['project'] = [
                'id'   => $shift->project_id,
                'name' => $shift->project?->name,
            ];
        }

        if (! empty($shift->room_id)) {
            $initial['room'] = [
                'id'   => $shift->room_id,
                'name' => $shift->room?->name,
            ];
        }

        if (! empty($shift->craft_id)) {
            $initial['craft'] = [
                'id'           => $shift->craft_id,
                'name'         => $shift->craft?->name,
                'abbreviation' => $shift->craft?->abbreviation,
            ];
        }
    }


    /**
     * Änderungen nach Festschreibung → committed_shift_changes.
     *
     * @param array<string,mixed> $meta
     * @param array<string,mixed> $fieldChanges
     * @param string $eventName
     * @return void
     */
    protected function recordCommittedChange(array $meta, array $fieldChanges, string $eventName): void
    {
        // 1) Immer einen "globalen" CommittedShiftChange schreiben
        CommittedShiftChange::create([
            'craft_id'                => $meta['craft_id'],
            'shift_id'                => $meta['shift_id'],
            'subject_type'            => $meta['subject_type'],
            'subject_id'              => $meta['subject_id'],
            'change_type'             => $eventName,
            'field_changes'           => $fieldChanges,
            'affected_user_type'      => $meta['affected_user_type'] ?? null,
            'affected_user_id'        => $meta['affected_user_id'] ?? null,
            'changed_by_user_id'      => $meta['changed_by_user_id'],
            'changed_at'              => now(),
            'acknowledged_at'         => null,
            'acknowledged_by_user_id' => null,
        ]);
    }


    /**
     * Normalisiert Datumsfelder im _initial-Array auf lokales Y-m-d.
     *
     * Hintergrund: DB speichert i.d.R. UTC (z.B. 2025-11-14 23:00:00),
     * wir wollen aber im Log das lokale Datum (z.B. 2025-11-15) sehen.
     */
    protected function normalizeInitialDates(array $initial): array
    {
        foreach (['start_date', 'end_date'] as $field) {
            if (empty($initial[$field])) {
                continue;
            }

            $value = $initial[$field];

            // Wenn schon sauberes Y-m-d, nichts machen
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                continue;
            }

            try {
                // Rohwert als UTC interpretieren und in App-Zeitzone umrechnen
                $initial[$field] = Carbon::parse($value, 'UTC')
                    ->setTimezone(config('app.timezone', 'Europe/Berlin'))
                    ->toDateString(); // 2025-11-15
            } catch (\Throwable $e) {
                // Im Zweifel: Wert unverändert lassen, damit nichts crasht
            }
        }

        return $initial;
    }

}
