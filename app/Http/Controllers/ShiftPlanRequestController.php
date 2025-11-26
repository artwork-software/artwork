<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftPlanRequestRequest;
use App\Http\Requests\UpdateShiftPlanRequestRequest;
use Artwork\Core\Services\HelperService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftPlanRequestChange;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftPlanRequestService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ShiftPlanRequestController extends Controller
{

    public function __construct(
        protected AuthManager $auth,
        protected HelperService $helperService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        $craftsWithShiftPlans = Craft::query()
            ->with(['shiftPlanRequests' => function ($query): void {
                $query->orderBy('created_at', 'desc');
            }])
            ->get();

        return Inertia::render('ShiftPlanRequests/Index', [
            'crafts' => $craftsWithShiftPlans,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftPlanRequestRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();

        /** @var \App\Models\User $user */
        $user = $this->auth->user();
        $data['requested_by_user_id'] = $user->id;

        // 1. Prüfen, ob es bereits ein PENDING-Request für Craft/KW/Jahr gibt
        $shiftPlanRequest = ShiftPlanRequest::query()
            ->where('craft_id', $data['craft_id'])
            ->where('week_number', $data['week_number'])
            ->where('year', $data['year'])
            ->where('status', 'pending')
            ->first();

        $service = app(ShiftPlanRequestService::class);

        // 2. Wenn keines existiert → neues anlegen, sonst vorhandenes nutzen
        if (! $shiftPlanRequest) {
            $shiftPlanRequest = $service->createRequestWithShifts($data, [], true);
        }

        // 3. Start/Ende der KW holen
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $shiftPlanRequest->week_number,
            $shiftPlanRequest->year
        );

        // 4. Schichten für dieses Gewerk in diesem Zeitraum,
        //    die NOCH NICHT im Workflow sind ODER aus einem abgelehnten Request stammen
        $shiftsQuery = Shift::query()
            ->where('craft_id', $shiftPlanRequest->craft_id)
            ->startAndEndDateOverlap($start->toDateString(), $end->toDateString())
            ->where('in_workflow', false)
            ->where(function ($q): void {
                $q
                    // komplett "freie" Schichten
                    ->whereNull('current_request_id')
                    // oder Schichten, die einem abgelehnten Request zugeordnet waren
                    ->orWhereHas('currentRequest', function ($sub): void {
                        $sub->where('status', 'rejected');
                    });
            });

        // optional: nur nicht festgeschriebene Schichten hinzufügen
        // $shiftsQuery->where('is_committed', false);

        // 5. Relevante Schichten vor dem Update holen (für Activity Log)
        $shifts = $shiftsQuery
            ->with('currentRequest') // wichtig für History-Log
            ->get();

        $shiftIdsToAttach = [];

        foreach ($shifts as $shift) {
            $previousRequest = null;

            // Falls die Schicht aus einem abgelehnten Request kommt, merken wir uns diesen
            if ($shift->currentRequest && $shift->currentRequest->status === 'rejected') {
                $previousRequest = $shift->currentRequest;
            }

            $activity = activity()
                ->performedOn($shift)
                ->causedBy($user)
                ->event('shift_added_to_request')
                ->withProperties([
                    'old' => [
                        'in_workflow'        => $shift->in_workflow,
                    ],
                    'attributes' => [
                        'in_workflow'        => true,
                    ],
                    'shift_plan_request_id'           => $shiftPlanRequest->id,
                    'previous_shift_plan_request_id'  => $previousRequest?->id,
                ]);

            // Zusatz-History, wenn aus abgelehntem Request übernommen
            if ($previousRequest) {
                $activity->tap(function (Activity $activity) use ($previousRequest, $shiftPlanRequest): void {
                    $props = $activity->properties ?? collect();

                    $props = $props->merge([
                        'translation_key' => 'Shift rejected in request from {0} with reason "{1}", now added to {2}',
                        'translation_key_placeholder_values' => [
                            // {0} = Datum des alten Requests
                            optional($previousRequest->created_at)->format('d.m.Y'),
                            // {1} = Ablehnungsgrund (Feldnamen ggf. anpassen)
                            $previousRequest->rejection_reason ?? '',
                            // {2} = Datum des neuen Requests
                            optional($shiftPlanRequest->created_at)->format('d.m.Y'),
                        ],
                    ]);

                    $activity->properties = $props;
                });
            }

            $activity->log('Shift added to shift plan request');

            $shiftIdsToAttach[] = $shift->id;
        }

        // 6. Performantes Massenupdate (nachdem geloggt wurde)
        if (! empty($shiftIdsToAttach)) {
            // Attach shifts to request (history pivot)
            $service->attachShiftsToRequest($shiftPlanRequest, $shiftIdsToAttach, true);

            $shiftsQuery->update([
                'current_request_id' => $shiftPlanRequest->id,
                'in_workflow'        => true,
            ]);
        }

        return back()->with(
            'success',
            $shiftPlanRequest->wasRecentlyCreated
                ? __('Shift plan request created successfully.')
                : __('Existing shift plan request updated successfully.')
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(ShiftPlanRequest $shiftPlanRequest): \Inertia\Response
    {
        $shiftPlanRequest->load([
            'craft',
            'requestedBy',
            'reviewedBy',
            'requestedShifts'
        ]);

        // Tage der KW berechnen (Mo–So)
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $shiftPlanRequest->week_number,
            $shiftPlanRequest->year
        );

        $days = collect(CarbonPeriod::create($start, $end))
            ->map(fn (Carbon $date) => [
                'date'        => $date->toDateString(),                 // 2025-11-04
                'label'       => $date->isoFormat('dd, DD.MM.'),        // Di, 04.11.
                'full_label'  => $date->isoFormat('dddd, DD.MM.YYYY'),  // Dienstag, 04.11.2025
                'is_today'    => $date->isToday(),
            ])
            ->values();

        // Alle Schichten, die zu diesem Request gehören
        $shifts = Shift::query()
            ->whereIn('id', $shiftPlanRequest->requestedShifts->pluck('id')->toArray())
            ->with([
                'users',
                'freelancer',
                'serviceProvider',
                'craft',
                'shiftPlanRequestChanges' => fn ($query) => $query->orderByDesc('created_at'),
                'shiftPlanRequestChanges.changedBy',
                'activities' => fn ($query) => $query->orderByDesc('created_at'),
                'activities.causer',
                'committedShiftChanges' => fn ($query) => $query->orderByDesc('created_at'),
                'committedShiftChanges.changedBy',
            ])
            ->get();

        return Inertia::render('ShiftPlanRequests/Show', [
            'request' => $shiftPlanRequest,
            'shifts'  => $shifts,
            'days'    => $days,
        ]);
    }

    public function accept(ShiftPlanRequest $shiftPlanRequest): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $this->auth->user();

        $shiftPlanRequest->status = 'approved';
        $shiftPlanRequest->reviewed_by_user_id = $user->id;
        $shiftPlanRequest->reviewed_at = now();
        $shiftPlanRequest->save();

        // commit all shifts in this request
        $shifts = Shift::query()
            ->where('current_request_id', $shiftPlanRequest->id)
            ->get();

        foreach ($shifts as $shift) {
            $shift->workflow_rejection_reason = null;
            $shift->is_committed = true;
            $shift->in_workflow = false;
            $shift->committing_user_id = $this->auth->id();
            $shift->save();

            activity()
                ->performedOn($shift)
                ->causedBy($user)
                ->event('shift_committed')
                ->log('Shift committed as part of approved shift plan request');
        }

        return back()->with(
            'success',
            __('Shift plan request approved successfully.')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftPlanRequest $shiftPlanRequest): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftPlanRequestRequest $request, ShiftPlanRequest $shiftPlanRequest): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftPlanRequest $shiftPlanRequest): void
    {
        //
    }

    public function reject(\Artwork\Modules\Shift\Models\ShiftPlanRequest $shiftPlanRequest, \Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $this->auth->user();

        $payload = $request->validate([
            'global_reason'     => ['nullable','string'],
            'days'              => ['array'],
            'days.*.date'       => ['required','date'],
            'days.*.reason'     => ['nullable','string'],
            'shifts'            => ['array'],
            'shifts.*.shift_id' => ['required','integer','exists:shifts,id'],
            'shifts.*.reason'   => ['nullable','string'],
        ]);

        // Request als abgelehnt markieren
        $shiftPlanRequest->status              = 'rejected';
        $shiftPlanRequest->reviewed_by_user_id = $user->id;
        $shiftPlanRequest->reviewed_at         = now();
        $shiftPlanRequest->review_comment      = $payload['global_reason'] ?? null; // globaler Grund
        $shiftPlanRequest->save();

        // Alle Schichten, die zu diesem Request gehören
        $shifts = Shift::query()
            ->where('current_request_id', $shiftPlanRequest->id)
            ->get();

        // Gründe pro Schicht
        $shiftReasons = collect($payload['shifts'] ?? [])
            ->mapWithKeys(fn($s) => [$s['shift_id'] => $s['reason']])
            ->filter(fn($r) => $r !== null && trim($r) !== '');

        // Gründe pro Tag
        $dayReasons = collect($payload['days'] ?? [])
            ->mapWithKeys(fn($d) => [$d['date'] => $d['reason']])
            ->filter(fn($r) => $r !== null && trim($r) !== '');

        // Felder, die im Workflow getrackt werden und auf _initial zurück sollen
        $fieldsToRevert = [
            'event_id',
            'start_date',
            'end_date',
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'description',
            'is_committed',
            'committing_user_id',
            'room_id',
            'project_id',
            'shift_group_id',
        ];

        foreach ($shifts as $shift) {
            $frontendStart = $shift->formatted_dates['frontend_start'] ?? null;

            if ($frontendStart) {
                $date = Carbon::parse($frontendStart)->toDateString(); // 2025-11-10
            } else {
                $date = $shift->event_start_day; // falls du das im Format Y-m-d speicherst
            }

            $reason = $shiftReasons->get($shift->id)
                ?? ($date ? $dayReasons->get($date) : null)
                ?? ($payload['global_reason'] ?? null);

            // ---------- 2) Initial-Daten aus erster ShiftPlanRequestChange holen ----------
            /** @var ShiftPlanRequestChange|null $firstChange */
            $firstChange = $shift->shiftPlanRequestChanges()
                ->orderBy('changed_at')
                ->orderBy('id')
                ->first();

            $initialData = null;
            if ($firstChange) {
                $fieldChanges = $firstChange->field_changes ?? [];
                if (is_array($fieldChanges) && isset($fieldChanges['_initial']) && is_array($fieldChanges['_initial'])) {
                    $initialData = $fieldChanges['_initial'];
                }
            }

            // Originalzustand der Schicht (für Activity-Log)
            $originalBeforeRollback = $shift->getAttributes();

            $rollbackFieldChanges = [];

            // ---------- 3) "normale" Felder auf _initial zurücksetzen ----------
            if ($initialData) {
                foreach ($fieldsToRevert as $field) {
                    if (! array_key_exists($field, $initialData)) {
                        continue;
                    }

                    $currentValue = $shift->{$field};
                    $initialValue = $initialData[$field];

                    // Datumfelder aus ISO-String / JSON normalisieren
                    if (in_array($field, ['start_date', 'end_date'], true) && ! empty($initialValue)) {
                        try {
                            // toDateString() ist safe:
                            // - für DATE-Spalten exakt richtig
                            // - für DATETIME wandelt MySQL "YYYY-MM-DD" zu "YYYY-MM-DD 00:00:00"
                            $initialValue = Carbon::parse($initialValue)->toDateString();
                        } catch (\Throwable $e) {
                            // im Zweifel den ursprünglichen Wert lassen, damit kein weiterer Crash passiert
                        }
                    }

                    if ($currentValue != $initialValue) {
                        $rollbackFieldChanges[$field] = [
                            'old' => $currentValue,
                            'new' => $initialValue,
                        ];

                        $shift->{$field} = $initialValue;
                    }
                }
            }

            // ---------- 4) GlobalQualifications + ShiftQualifications zurücksetzen ----------
            $shift->loadMissing(['globalQualifications', 'shiftsQualifications']);

            // 4a) GlobalQualifications
            if ($initialData && array_key_exists('global_qualifications', $initialData)) {
                $initialGlobals = collect($initialData['global_qualifications'] ?? [])
                    ->keyBy(fn ($item) => (int) $item['global_qualification_id']);

                // "Vorher"-Mengen aus Relation (pivot.quantity oder value)
                $beforeGlobals = $shift->globalQualifications
                    ->mapWithKeys(static function ($gq) {
                        $id  = (int) $gq->global_qualification_id;
                        $qty = $gq->pivot->quantity ?? $gq->value ?? 0;

                        return [$id => (int) $qty];
                    })
                    ->toArray();

                // Payload für sync() anhand _initial aufbauen
                $syncPayload = [];
                foreach ($initialGlobals as $id => $item) {
                    $qty = (int) ($item['value'] ?? $item['quantity'] ?? 0);
                    if ($qty > 0) {
                        $syncPayload[$id] = ['quantity' => $qty];
                    }
                }

                // Revert in DB
                $shift->globalQualifications()->sync($syncPayload);

                // "Nachher"-Mengen aus Payload
                $afterGlobals = [];
                foreach ($syncPayload as $id => $row) {
                    $afterGlobals[(int) $id] = (int) ($row['quantity'] ?? 0);
                }

                // Alle IDs, die betroffen sein könnten
                $allGlobalIds = collect(array_keys($beforeGlobals))
                    ->merge(array_keys($afterGlobals))
                    ->unique()
                    ->values()
                    ->all();

                foreach ($allGlobalIds as $id) {
                    $old = (int) ($beforeGlobals[$id] ?? 0);
                    $new = (int) ($afterGlobals[$id] ?? 0);

                    if ($old === $new) {
                        continue;
                    }

                    $item  = $initialGlobals->get((int) $id);
                    $label = $item['label'] ?? null;

                    $rollbackFieldChanges['global_qualifications'][] = [
                        'global_qualification_id' => (int) $id,
                        'label'                   => $label,
                        'old'                     => $old,
                        'new'                     => $new,
                        'kind'                    => 'global',
                    ];
                }
            }

            // 4b) ShiftQualifications (ShiftsQualifications-Modelle)
            if ($initialData && array_key_exists('shifts_qualifications', $initialData)) {
                $initialShiftQuals = collect($initialData['shifts_qualifications'] ?? [])
                    ->keyBy(fn ($item) => (int) $item['shift_qualification_id']);

                // "Vorher"-Mengen
                $beforeShiftQuals = $shift->shiftsQualifications
                    ->mapWithKeys(static function ($sq) {
                        return [(int) $sq->shift_qualification_id => (int) $sq->value];
                    })
                    ->toArray();

                // Zielmengen aus _initial
                $targetShiftQuantities = [];
                foreach ($initialShiftQuals as $id => $item) {
                    $targetShiftQuantities[(int) $id] = (int) ($item['value'] ?? 0);
                }

                // IDs, die in Zukunft existieren sollen (value > 0)
                $idsToKeep = collect($targetShiftQuantities)
                    ->filter(fn ($v) => $v > 0)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                // Erst alle entfernen, die nicht mehr existieren sollen
                if (! empty($idsToKeep)) {
                    $shift->shiftsQualifications()
                        ->whereNotIn('shift_qualification_id', $idsToKeep)
                        ->delete();
                } else {
                    // Wenn keine übrig bleiben sollen → alles löschen
                    $shift->shiftsQualifications()->delete();
                }

                // Reload, damit wir mit aktuellem Stand weiterarbeiten
                $shift->load('shiftsQualifications');

                // Jetzt für alle Ziel-IDs die Werte anpassen / neu anlegen
                foreach ($targetShiftQuantities as $id => $targetValue) {
                    /** @var \Artwork\Modules\Shift\Models\ShiftsQualifications|null $sq */
                    $sq = $shift->shiftsQualifications
                        ->firstWhere('shift_qualification_id', (int) $id);

                    if ($targetValue <= 0) {
                        // Sollte es noch existieren, löschen wir es
                        if ($sq) {
                            $sq->delete();
                        }
                        continue;
                    }

                    if (! $sq) {
                        // neu anlegen
                        $sq = new ShiftsQualifications();
                        $sq->shift_id               = $shift->id;
                        $sq->shift_qualification_id = (int) $id;
                    }

                    $sq->value = $targetValue;
                    $sq->save();
                }

                // Reload nach Änderungen
                $shift->load('shiftsQualifications');

                // "Nachher"-Mengen
                $afterShiftQuals = $shift->shiftsQualifications
                    ->mapWithKeys(static function ($sq) {
                        return [(int) $sq->shift_qualification_id => (int) $sq->value];
                    })
                    ->toArray();

                $allShiftQualIds = collect(array_keys($beforeShiftQuals))
                    ->merge(array_keys($afterShiftQuals))
                    ->unique()
                    ->values()
                    ->all();

                foreach ($allShiftQualIds as $id) {
                    $old = (int) ($beforeShiftQuals[$id] ?? 0);
                    $new = (int) ($afterShiftQuals[$id] ?? 0);

                    if ($old === $new) {
                        continue;
                    }

                    $item  = $initialShiftQuals->get((int) $id);
                    $label = $item['label'] ?? null;

                    $rollbackFieldChanges['shifts_qualifications'][] = [
                        'shift_qualification_id' => (int) $id,
                        'label'                  => $label,
                        'old'                    => $old,
                        'new'                    => $new,
                        'kind'                   => 'normal',
                    ];
                }
            }

            // ---------- 5) Workflow-Felder setzen / Activity-Log ----------
            $old = [
                'in_workflow'               => $originalBeforeRollback['in_workflow'] ?? (bool) $shift->in_workflow,
                'workflow_rejection_reason' => $originalBeforeRollback['workflow_rejection_reason'] ?? $shift->workflow_rejection_reason,
            ];

            $shift->workflow_rejection_reason = $reason;
            $shift->in_workflow               = false; // aus Workflow entfernen
            $shift->is_committed              = false; // Sicherheitshalber nicht festgeschrieben
            // $shift->current_request_id kannst du lassen, wenn du die Zuordnung behalten willst

            $shift->save();

            // ---------- 6) Rollback als eigenen ShiftPlanRequestChange-Eintrag loggen ----------
            if (! empty($rollbackFieldChanges)) {
                if ($initialData) {
                    $rollbackFieldChanges['_initial'] = $initialData;
                }

                ShiftPlanRequestChange::create([
                    'shift_plan_request_id' => $shiftPlanRequest->id,
                    'subject_type'          => Shift::class,
                    'subject_id'            => $shift->id,
                    'change_type'           => 'rejected', // oder 'rollback'
                    'field_changes'         => $rollbackFieldChanges,
                    'affected_user_id'      => null,
                    'changed_by_user_id'    => $user->id,
                    'changed_at'            => now(),
                ]);
            }

            // ---------- 7) Activity-Log für die Ablehnung ----------
            activity()
                ->performedOn($shift)
                ->causedBy($user)
                ->event('shift_rejected')
                ->withProperties([
                    'old' => $old,
                    'attributes' => [
                        'in_workflow'               => $shift->in_workflow,
                        'workflow_rejection_reason' => $shift->workflow_rejection_reason,
                    ],
                    'shift_plan_request_id' => $shiftPlanRequest->id,
                ])
                ->tap(function (Activity $activity) use ($shift): void {
                    $activity->properties = $activity->properties->merge([
                        'translation_key' => 'Shift rejected with reason: "{0}"',
                        'translation_key_placeholder_values' => [
                            $shift->workflow_rejection_reason ?? __('No reason provided'),
                        ],
                    ]);
                })
                ->log('Shift rejected as part of shift plan request');
        }

        return back()->with('success', __('Shift plan request rejected successfully.'));
    }

    public function changes(?Craft $craft = null): \Inertia\Response
    {
        $allCrafts = Craft::orderBy('name')->get();

        $changesQuery = CommittedShiftChange::query()
            ->with(['shift.craft', 'changedBy']) // shift.craft dazu
            ->when($craft, fn ($q) => $q->where('craft_id', $craft->id))
            ->orderByDesc('changed_at');

        $changes = $changesQuery->get()->map(function (CommittedShiftChange $change) {
            $fieldChanges = $change->field_changes ?? [];

            $assignment = $fieldChanges['assignment'] ?? null;

            $affectedName       = null;
            $profilePictureUrl  = null;
            $beforeLabel        = null;
            $afterLabel         = null;

            // Fall 1: User-Zuweisung / Entfernen -> Daten aus assignment
            if ($assignment) {
                $affectedName      = $assignment['user_name']           ?? null;
                $profilePictureUrl = $assignment['profile_picture_url'] ?? null;
                $beforeLabel       = $assignment['before_label']        ?? null;
                $afterLabel        = $assignment['after_label']         ?? null;
            } else {
                // Fall 2: reine Schicht-Änderung (start/end/break …)
                $shift = $change->shift;

                if ($shift) {
                    $date = optional($shift->start_date)?->format('d.m.Y')
                        ?? optional($shift->end_date)?->format('d.m.Y');

                    // Zeiten zuerst aus field_changes lesen
                    $beforeStart = $fieldChanges['start']['old'] ?? null;
                    $beforeEnd   = $fieldChanges['end']['old']   ?? null;
                    $afterStart  = $fieldChanges['start']['new'] ?? null;
                    $afterEnd    = $fieldChanges['end']['new']   ?? null;

                    // Falls dort nichts steht, auf aktuelle Shift-Werte zurückfallen
                    if (! $beforeStart && $shift->start) {
                        $beforeStart = $shift->start instanceof \Carbon\Carbon
                            ? $shift->start->format('H:i')
                            : (string) $shift->start;
                    }

                    if (! $beforeEnd && $shift->end) {
                        $beforeEnd = $shift->end instanceof \Carbon\Carbon
                            ? $shift->end->format('H:i')
                            : (string) $shift->end;
                    }

                    if (! $afterStart && $shift->start) {
                        $afterStart = $shift->start instanceof \Carbon\Carbon
                            ? $shift->start->format('H:i')
                            : (string) $shift->start;
                    }

                    if (! $afterEnd && $shift->end) {
                        $afterEnd = $shift->end instanceof \Carbon\Carbon
                            ? $shift->end->format('H:i')
                            : (string) $shift->end;
                    }

                    // BEFORE-Label bauen
                    if ($date && $beforeStart && $beforeEnd) {
                        $beforeLabel = sprintf('%s %s - %s', $date, $beforeStart, $beforeEnd);
                    } elseif ($beforeStart && $beforeEnd) {
                        $beforeLabel = sprintf('%s - %s', $beforeStart, $beforeEnd);
                    } elseif ($beforeEnd) {
                        $beforeLabel = $beforeEnd;
                    }

                    // AFTER-Label bauen
                    if ($date && $afterStart && $afterEnd) {
                        $afterLabel = sprintf('%s %s - %s', $date, $afterStart, $afterEnd);
                    } elseif ($afterStart && $afterEnd) {
                        $afterLabel = sprintf('%s - %s', $afterStart, $afterEnd);
                    } elseif ($afterEnd) {
                        $afterLabel = $afterEnd;
                    }

                    // „Betroffene Entität“ für reine Schicht-Änderung sinnvoll benennen
                    $craftAbbr = optional($shift->craft)->abbreviation;
                    $affectedName = $craftAbbr
                        ? sprintf('%s – %s', $craftAbbr, $date)
                        : ($date ?: null);
                }
            }

            return [
                'id'                     => $change->id,
                'change_type'            => $change->change_type,

                'affected_name'          => $affectedName,
                'profile_picture_url'    => $profilePictureUrl,

                'before_label'           => $beforeLabel,
                'after_label'            => $afterLabel,

                'changed_by_name'        => optional($change->changedBy)->full_name,
                'changed_at'             => optional($change->changed_at)?->toIso8601String(),
                'changed_at_formatted'   => optional($change->changed_at)?->format('d.m.Y H:i'),

                'acknowledged_at'        => optional($change->acknowledged_at)?->toIso8601String(),
                'acknowledged'           => ! is_null($change->acknowledged_at),

                'field_changes'          => $fieldChanges,
            ];
        });

        return Inertia::render('ShiftPlanRequests/Changes', [
            'allCrafts' => $allCrafts,
            'craft'     => $craft,
            'changes'   => $changes,
        ]);
    }



    /**
     * Nachträgliche Zustimmung zu einer Änderung.
     */
    public function acknowledge(CommittedShiftChange $change): \Illuminate\Http\RedirectResponse
    {
        if (is_null($change->acknowledged_at)) {
            $change->acknowledged_at = now();
            $change->acknowledged_by_user_id = auth()->id();
            $change->save();
        }

        return back()->with('success', __('Änderung wurde bestätigt.'));
    }

    public function requests(): \Inertia\Response
    {
        $shiftPlanRequests = ShiftPlanRequest::with(['craft', 'requestedBy'])
            ->where('requested_by_user_id', $this->auth->id())
            ->orderByDesc('created_at')
            ->get();

        // Group requests by craft and build a simple crafts collection that includes shift_plan_requests
        $grouped = $shiftPlanRequests->groupBy(fn ($r) => $r->craft->id ?? 0);

        $crafts = $grouped->map(function ($requests) {
            $first = $requests->first();
            $craft = $first->craft;

            // attach a simpler array expected by the frontend
            $craft->shift_plan_requests = $requests->map(function ($r) {
                return [
                    'id' => $r->id,
                    'week_number' => $r->week_number,
                    'year' => $r->year,
                    'requested_at' => optional($r->created_at)?->toIso8601String(),
                    'status' => $r->status,
                ];
            })->values();

            return $craft;
        })->values();

        return Inertia::render('ShiftPlanRequests/MyIndex', [
            'crafts' => $crafts,
        ]);
    }

    /**
     * Read-only show for "my" view.
     */
    public function myShow(ShiftPlanRequest $shiftPlanRequest): \Inertia\Response
    {
        $shiftPlanRequest->load([
            'craft',
            'requestedBy',
            'reviewedBy',
            'requestedShifts'
        ]);

        // Tage der KW berechnen (Mo–So)
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $shiftPlanRequest->week_number,
            $shiftPlanRequest->year
        );

        $days = collect(CarbonPeriod::create($start, $end))
            ->map(fn (Carbon $date) => [
                'date'        => $date->toDateString(),                 // 2025-11-04
                'label'       => $date->isoFormat('dd, DD.MM.'),        // Di, 04.11.
                'full_label'  => $date->isoFormat('dddd, DD.MM.YYYY'),  // Dienstag, 04.11.2025
                'is_today'    => $date->isToday(),
            ])
            ->values();

        // Alle Schichten, die zu diesem Request gehören
        $shifts = Shift::query()
            ->whereIn('id', $shiftPlanRequest->requestedShifts->pluck('id')->toArray())
            ->with([
                'users',
                'freelancer',
                'serviceProvider',
                'craft',
                'shiftPlanRequestChanges' => fn ($query) => $query->orderByDesc('created_at'),
                'shiftPlanRequestChanges.changedBy',
                'activities' => fn ($query) => $query->orderByDesc('created_at'),
                'activities.causer',
                'committedShiftChanges' => fn ($query) => $query->orderByDesc('created_at'),
                'committedShiftChanges.changedBy',
            ])
            ->get();

        return Inertia::render('ShiftPlanRequests/Show', [
            'request' => $shiftPlanRequest,
            'shifts'  => $shifts,
            'days'    => $days,
            'isMyRequest' => true,
        ]);
    }

    /**
     * Setzt eine einzelne Änderung eines ShiftPlanRequests gezielt zurück (inkl. Qualifikationen etc.).
     * @param int $requestId
     * @param int $changeId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revertChange(ShiftPlanRequest $shiftPlanRequest, ShiftPlanRequestChange $shiftChange)
    {
        /** @var User $user */
        $user = $this->auth->user();
        // Zugehörige Schicht laden
        $shift = Shift::find($shiftChange->subject_id) ?? null;

        if (! $shift) {
            return back()->withErrors([
                'message' => 'The shift associated with this change could not be found.',
            ]);
        }

        // Optionaler Sicherheitscheck: Change gehört zu diesem Request?
        if ((int) $shift->current_request_id !== (int) $shiftPlanRequest->id) {
            return back()->withErrors([
                'message' => 'The specified change does not belong to the provided shift plan request.',
            ]);
        }

        $fieldChanges = $shiftChange->field_changes ?? [];

        if (! is_array($fieldChanges) || empty($fieldChanges)) {
            return back()->withErrors([
                'message' => 'No field changes found to revert.',
            ]);
        }

        return DB::transaction(function () use ($shiftPlanRequest, $shiftChange, $shift, $fieldChanges, $user) {
            $rollbackFieldChanges = [];

            // Originalzustand der Schicht für Activity-Log
            $originalAttributes = $shift->getAttributes();

            // Relationen, die wir brauchen könnten
            $shift->loadMissing(['globalQualifications', 'shiftsQualifications']);

            foreach ($fieldChanges as $field => $value) {
                // _initial wird absichtlich ignoriert – wir wollen nur DIESE Änderung zurückdrehen
                if ($field === '_initial') {
                    continue;
                }

                /**
                 * 1) "normale" Felder mit ['old' => ..., 'new' => ...]
                 * (z.B. break_minutes, start, end, description, ...)
                 */
                if (
                    ! in_array($field, ['qualifications', 'global_qualifications'], true)
                    && is_array($value)
                    && array_key_exists('old', $value)
                    && array_key_exists('new', $value)
                ) {
                    $oldValue     = $value['old'];
                    $currentValue = $shift->{$field};

                    if ($currentValue != $oldValue) {
                        $rollbackFieldChanges[$field] = [
                            'old' => $currentValue,
                            'new' => $oldValue,
                        ];

                        $shift->{$field} = $oldValue;
                    }

                    continue;
                }

                /**
                 * 2) Shift-Qualifikationen
                 * field_changes:
                 * {
                 *   "qualifications":[{"qualification_id":1,"label":"Mitarbeiter","old":5,"new":10,"kind":"normal"}]
                 * }
                 */
                if ($field === 'qualifications' && is_array($value)) {
                    // "Vorher"-Stand merken
                    $beforeQuals = $shift->shiftsQualifications
                        ->mapWithKeys(static function ($sq) {
                            return [(int) $sq->shift_qualification_id => (int) $sq->value];
                        })
                        ->toArray();

                    $touchedIds = [];
                    $items      = collect($value);

                    foreach ($items as $item) {
                        $id = (int) ($item['qualification_id'] ?? 0);
                        if ($id <= 0) {
                            continue;
                        }

                        $oldQty = (int) ($item['old'] ?? 0);
                        $touchedIds[] = $id;

                        /** @var \Artwork\Modules\Shift\Models\ShiftsQualifications|null $sq */
                        $sq = $shift->shiftsQualifications
                            ->firstWhere('shift_qualification_id', $id);

                        if ($oldQty <= 0) {
                            // Sollte nicht mehr existieren → löschen
                            if ($sq) {
                                $sq->delete();
                            }
                        } else {
                            // Menge wieder auf "old" setzen
                            if (! $sq) {
                                $sq                           = new ShiftsQualifications();
                                $sq->shift_id                = $shift->id;
                                $sq->shift_qualification_id  = $id;
                            }

                            $sq->value = $oldQty;
                            $sq->save();
                        }
                    }

                    // Reload nach Änderungen
                    $shift->load('shiftsQualifications');

                    $afterQuals = $shift->shiftsQualifications
                        ->mapWithKeys(static function ($sq) {
                            return [(int) $sq->shift_qualification_id => (int) $sq->value];
                        })
                        ->toArray();

                    foreach (array_unique($touchedIds) as $id) {
                        $oldQty = (int) ($beforeQuals[$id] ?? 0);
                        $newQty = (int) ($afterQuals[$id] ?? 0);

                        if ($oldQty === $newQty) {
                            continue;
                        }

                        $item = $items->firstWhere('qualification_id', $id);

                        $rollbackFieldChanges['qualifications'][] = [
                            'qualification_id' => (int) $id,
                            'label'            => $item['label'] ?? null,
                            'old'              => $oldQty,
                            'new'              => $newQty,
                            'kind'             => $item['kind'] ?? null,
                        ];
                    }

                    continue;
                }

                /**
                 * 3) Global-Qualifikationen
                 * field_changes:
                 * {
                 *   "global_qualifications":[{"global_qualification_id":1,"label":"Hi","old":0,"new":15,"kind":"global"}]
                 * }
                 */
                if ($field === 'global_qualifications' && is_array($value)) {
                    // "Vorher"-Stand
                    $beforeGlobals = $shift->globalQualifications
                        ->mapWithKeys(static function ($gq) {
                            $id  = (int) $gq->global_qualification_id;
                            $qty = $gq->pivot->quantity ?? $gq->value ?? 0;

                            return [$id => (int) $qty];
                        })
                        ->toArray();

                    $relation  = $shift->globalQualifications();
                    $touchedIds = [];
                    $items      = collect($value);

                    foreach ($items as $item) {
                        $id = (int) ($item['global_qualification_id'] ?? 0);
                        if ($id <= 0) {
                            continue;
                        }

                        $oldQty = (int) ($item['old'] ?? 0);
                        $touchedIds[] = $id;

                        if ($oldQty <= 0) {
                            // Sollte nicht mehr existieren
                            $relation->detach($id);
                        } else {
                            // Menge wieder auf "old"
                            $relation->syncWithoutDetaching([
                                $id => ['quantity' => $oldQty],
                            ]);
                        }
                    }

                    // Reload nach Änderungen
                    $shift->load('globalQualifications');

                    $afterGlobals = $shift->globalQualifications
                        ->mapWithKeys(static function ($gq) {
                            $id  = (int) $gq->global_qualification_id;
                            $qty = $gq->pivot->quantity ?? $gq->value ?? 0;

                            return [$id => (int) $qty];
                        })
                        ->toArray();

                    foreach (array_unique($touchedIds) as $id) {
                        $oldQty = (int) ($beforeGlobals[$id] ?? 0);
                        $newQty = (int) ($afterGlobals[$id] ?? 0);

                        if ($oldQty === $newQty) {
                            continue;
                        }

                        $item = $items->firstWhere('global_qualification_id', $id);

                        $rollbackFieldChanges['global_qualifications'][] = [
                            'global_qualification_id' => (int) $id,
                            'label'                   => $item['label'] ?? null,
                            'old'                     => $oldQty,
                            'new'                     => $newQty,
                            'kind'                    => $item['kind'] ?? null,
                        ];
                    }

                    continue;
                }
            }

            // Schicht nach allen Anpassungen speichern
            $shift->save();

            // Neuen Change-Eintrag als "Rollback" loggen (damit History vollständig bleibt)
            if (! empty($rollbackFieldChanges) && $shift->is_committed) {
                CommittedShiftChange::create([
                    'subject_type'      => Shift::class,
                    'subject_id'        => $shift->id,
                    'shift_id'           => $shift->id,
                    'craft_id'           => $shift->craft_id,
                    'change_type'        => 'revert',
                    'field_changes'      => $rollbackFieldChanges,
                    'affected_user_id'   => $shiftChange->affected_user_id ?? null,
                    'changed_by_user_id' => $user->id,
                    'changed_at'         => now(),
                ]);
            }

            // Activity-Log – nur wirklich geänderte Felder loggen
            if (! empty($rollbackFieldChanges)) {
                activity()
                    ->performedOn($shift)
                    ->causedBy($user)
                    ->event('committed_shift_change_reverted')
                    ->withProperties([
                        'shift_plan_request_id' => $shiftPlanRequest->id,
                        'shift_change_id'       => $shiftChange->id,
                        // nur die zurückgedrehten Felder + alte/neue Werte
                        'field_changes'         => $rollbackFieldChanges,
                    ])
                    ->log('Committed shift change reverted');
            }


            $shiftChange->delete();

            return back()->with('success', 'Shift reverted');
        });
    }
}
