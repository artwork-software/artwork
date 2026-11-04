<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Support\ShiftHistoryDayEntryPerson;
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
     * Wenn true, werden KEINE Änderungs-Einträge (ShiftPlanRequestChange / CommittedShiftChange)
     * geschrieben. Wird z.B. beim Zurücksetzen einer Änderung (revertChange) gesetzt, damit das
     * Rückgängigmachen selbst nicht erneut als Änderung im Verlauf auftaucht.
     */
    protected static bool $suppressed = false;

    /**
     * Führt $callback aus, ohne dass dabei ausgelöste Model-Events Änderungs-Einträge erzeugen.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function withoutRecording(callable $callback): mixed
    {
        $previous = self::$suppressed;
        self::$suppressed = true;

        try {
            return $callback();
        } finally {
            self::$suppressed = $previous;
        }
    }

    /**
     * Haupteinstiegspunkt: von Observern aus aufrufen.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $eventName created|updated|deleted
     */
    public function record(Model $model, string $eventName): void
    {
        if (self::$suppressed) {
            return;
        }

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

    /** change_type für nachträglich geänderte bzw. gelöschte freigegebene individuelle Zeiten */
    public const INDIVIDUAL_TIME_UPDATED = 'individual_time_updated';
    public const INDIVIDUAL_TIME_DELETED = 'individual_time_deleted';

    /**
     * Freigegebene individuelle Zeiten verhalten sich wie festgeschriebene Schichten: Änderungen und
     * Löschungen bleiben freigegeben, landen aber als offene Änderung nach Freigabe in
     * committed_shift_changes (Änderungsübersicht, gelbe KW-Kachel bis zur Bestätigung).
     *
     * field_changes: je geändertem Feld {old, new} bzw. `_deleted` mit dem letzten Stand, dazu
     * `_individual_time` als Stand der Zeit (nach der Änderung bzw. vor dem Löschen) mit Person und
     * Datum — die Zeile existiert beim Löschen nicht mehr und hat keine Schicht.
     *
     * Aufruf aus den Model-Events updated/deleted (IndividualTime::booted).
     */
    public function recordIndividualTimeChange(IndividualTime $individualTime, string $eventName): void
    {
        if (self::$suppressed || ! in_array($eventName, ['updated', 'deleted'], true)) {
            return;
        }

        // Nur freigegebene Zeiten; Wechsel des Freigabe-Status selbst ist keine Änderung
        if (! $individualTime->getOriginal('is_committed') || $individualTime->wasChanged('is_committed')) {
            return;
        }

        $original = $individualTime->getOriginal();
        $fieldChanges = [];

        if ($eventName === 'deleted') {
            $fieldChanges['_deleted'] = $this->individualTimeState($original);
        } else {
            foreach (array_keys($individualTime->getChanges()) as $field) {
                if (! in_array($field, IndividualTime::CONTENT_FIELDS, true)) {
                    continue;
                }
                $old = $this->normalizeIndividualTimeValue($field, $original[$field] ?? null);
                $new = $this->normalizeIndividualTimeValue($field, $individualTime->getAttribute($field));
                if ($old == $new) {
                    continue;
                }
                $fieldChanges[$field] = ['old' => $old, 'new' => $new];
            }

            if ($fieldChanges === []) {
                return;
            }
        }

        $state = $eventName === 'deleted' ? $original : $individualTime->getAttributes();
        $timeableType = $state['timeable_type'] ?? null;
        $timeableId = $state['timeable_id'] ?? null;

        $fieldChanges['_individual_time'] = $this->individualTimeState($state) + [
            'person_name' => $this->individualTimeOwnerName($timeableType, $timeableId),
        ];

        CommittedShiftChange::create([
            'craft_id'                => $this->approvingCraftId($individualTime),
            'shift_id'                => null,
            'subject_type'            => IndividualTime::class,
            'subject_id'              => $individualTime->getKey(),
            'change_type'             => $eventName === 'deleted'
                ? self::INDIVIDUAL_TIME_DELETED
                : self::INDIVIDUAL_TIME_UPDATED,
            'field_changes'           => $fieldChanges,
            'affected_user_type'      => $timeableType,
            'affected_user_id'        => $timeableId,
            'changed_by_user_id'      => Auth::id(),
            'changed_at'              => now(),
            'acknowledged_at'         => null,
            'acknowledged_by_user_id' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected function individualTimeState(array $attributes): array
    {
        $state = [];
        $fields = [
            'title', 'start_date', 'end_date', 'start_time', 'end_time', 'full_day', 'working_time_minutes',
            'break_minutes',
        ];
        foreach ($fields as $field) {
            $state[$field] = $this->normalizeIndividualTimeValue($field, $attributes[$field] ?? null);
        }

        return $state;
    }

    protected function normalizeIndividualTimeValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'start_date', 'end_date' => Carbon::parse($value)->toDateString(),
            'start_time', 'end_time' => substr((string) $value, 0, 5),
            'full_day' => (bool) $value,
            'working_time_minutes', 'break_minutes', 'timeable_id' => (int) $value,
            default => $value,
        };
    }

    protected function individualTimeOwnerName(?string $type, mixed $id): ?string
    {
        // gecacht pro Request: beim Löschen einer Serie kommt dieselbe Person viele Male vor
        return ShiftHistoryDayEntryPerson::nameOf($type, $id !== null ? (int) $id : null);
    }

    /**
     * Gewerk der Anfrage, mit der die Zeit freigegeben wurde (Filter der Änderungsübersicht).
     */
    protected function approvingCraftId(IndividualTime $individualTime): ?int
    {
        $craftId = $individualTime->shiftPlanRequests()
            ->where('status', 'approved')
            ->orderByDesc('shift_plan_requests.id')
            ->value('shift_plan_requests.craft_id');

        return $craftId !== null ? (int) $craftId : null;
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

            // Datums-Felder lokal normalisieren: getOriginal() liefert durch den
            // datetime-Cast Carbon-Objekte, die json_encode als UTC-ISO serialisiert
            // ("2025-11-14T23:00:00Z" für den lokalen 15.11.). Beim Revert würde der
            // Wert unkonvertiert in die DATE-Spalte geschrieben und die Schicht um
            // einen Tag verrutschen — gleiche Bug-Klasse wie bei normalizeInitialDates.
            if (in_array($key, ['start_date', 'end_date'], true)) {
                $oldValue = $this->normalizeDateValue($oldValue);
                $newValue = $this->normalizeDateValue($newValue);
            }

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
        if (self::$suppressed) {
            return;
        }

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
        if (self::$suppressed) {
            return;
        }

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
        // When a Shift is deleted, the row no longer exists in the shifts table.
        // Setting shift_id to null avoids FK constraint violation; shift data is preserved in field_changes.
        $shiftId = ($eventName === 'deleted' && $meta['subject_type'] === Shift::class)
            ? null
            : $meta['shift_id'];

        // 1) Immer einen "globalen" CommittedShiftChange schreiben
        CommittedShiftChange::create([
            'craft_id'                => $meta['craft_id'],
            'shift_id'                => $shiftId,
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
    /**
     * Einzelnen Datumswert (Carbon-Objekt oder ISO-/UTC-String) auf das lokale
     * Y-m-d-Datum normalisieren — Pendant zu normalizeInitialDates für die
     * per-Feld old/new-Werte.
     */
    protected function normalizeDateValue(mixed $value): mixed
    {
        if (empty($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (!is_string($value) && !$value instanceof \DateTimeInterface) {
            return $value;
        }

        try {
            return Carbon::parse($value, 'UTC')
                ->setTimezone(config('app.timezone', 'Europe/Berlin'))
                ->toDateString();
        } catch (\Throwable $e) {
            return $value;
        }
    }

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
