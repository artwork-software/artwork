<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftPlanRequestRequest;
use App\Http\Requests\UpdateShiftPlanRequestRequest;
use Artwork\Core\Services\HelperService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftPlanRequestChange;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\Shift\Services\ShiftPlanRequestService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Artwork\Modules\Role\Enums\RoleEnum;
use Spatie\Activitylog\Models\Activity;

class ShiftPlanRequestController extends Controller
{

    public function __construct(
        protected AuthManager $auth,
        protected HelperService $helperService
    ) {
    }

    /**
     * Lädt die Schichten zur Anzeige einer Anfrage:
     *  - die ursprünglich angefragten Schichten (Pivot) → is_subsequently_added = false
     *  - nachträglich hinzugefügte Schichten: Schichten desselben Gewerks in derselben KW, die nach
     *    dem Anlegen der Anfrage erstellt wurden und nicht Teil der Anfrage sind
     *    → is_subsequently_added = true
     *
     * @return \Illuminate\Database\Eloquent\Collection<int,Shift>
     */
    private function loadShiftsForRequest(
        ShiftPlanRequest $shiftPlanRequest,
        Carbon $start,
        Carbon $end
    ): \Illuminate\Database\Eloquent\Collection {
        $relations = [
            'users',
            'freelancer',
            'serviceProvider',
            'craft',
            'shiftsQualifications',
            // Nur Workflow-Änderungen, die zu DIESER Anfrage gehören (nicht aus früheren Anfragen
            // derselben Schicht), damit der "Änderung angefordert"-Marker nicht fälschlich greift.
            'shiftPlanRequestChanges' => fn ($query) => $query
                ->where('shift_plan_request_id', $shiftPlanRequest->id)
                ->orderByDesc('created_at'),
            'shiftPlanRequestChanges.changedBy',
            'activities' => fn ($query) => $query->orderByDesc('created_at'),
            'activities.causer',
            // Nur nachträgliche (post-commit) Änderungen, die NACH dem Stellen der Anfrage
            // passiert sind, damit der "Geändert"-Marker nur echte nachträgliche Änderungen zeigt.
            'committedShiftChanges' => fn ($query) => $query
                ->when(
                    $shiftPlanRequest->created_at,
                    fn ($q) => $q->where('changed_at', '>=', $shiftPlanRequest->created_at)
                )
                ->orderByDesc('created_at'),
            'committedShiftChanges.changedBy',
        ];

        $requestedShiftIds = $shiftPlanRequest->requestedShifts->pluck('id')->toArray();

        $requestedShifts = Shift::query()
            ->whereIn('id', $requestedShiftIds)
            ->with($relations)
            ->get()
            ->each(fn (Shift $shift) => $shift->setAttribute('is_subsequently_added', false));

        $addedShifts = Shift::query()
            ->where('craft_id', $shiftPlanRequest->craft_id)
            ->startAndEndDateOverlap($start->toDateString(), $end->toDateString())
            ->when(
                ! empty($requestedShiftIds),
                fn ($q) => $q->whereNotIn('id', $requestedShiftIds)
            )
            ->when(
                $shiftPlanRequest->created_at,
                fn ($q) => $q->where('created_at', '>=', $shiftPlanRequest->created_at)
            )
            ->with($relations)
            ->get()
            ->each(fn (Shift $shift) => $shift->setAttribute('is_subsequently_added', true));

        return $requestedShifts->concat($addedShifts)->values();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        $fourWeeksAgo = Carbon::now()->subWeeks(4);
        $cutoffYear = (int) $fourWeeksAgo->format('o');
        $cutoffWeek = (int) $fourWeeksAgo->format('W');

        $craftsWithShiftPlans = Craft::query()
            ->with(['shiftPlanRequests' => function ($query) use ($cutoffYear, $cutoffWeek): void {
                $query->where(function ($q) use ($cutoffYear, $cutoffWeek) {
                    $q->where('status', 'pending')
                        ->orWhere(function ($q2) use ($cutoffYear, $cutoffWeek) {
                            $q2->whereIn('status', ['approved', 'rejected'])
                                ->where(function ($q3) use ($cutoffYear, $cutoffWeek) {
                                    $q3->where('year', '>', $cutoffYear)
                                        ->orWhere(function ($q4) use ($cutoffYear, $cutoffWeek) {
                                            $q4->where('year', '=', $cutoffYear)
                                                ->where('week_number', '>=', $cutoffWeek);
                                        });
                                });
                        });
                })
                ->orderByDesc('year')
                ->orderByDesc('week_number');
            }])
            ->withCount([
                'shiftPlanRequests as past_approved_count' => function ($q) use ($cutoffYear, $cutoffWeek) {
                    $q->where('status', 'approved')
                        ->where(function ($q2) use ($cutoffYear, $cutoffWeek) {
                            $q2->where('year', '<', $cutoffYear)
                                ->orWhere(function ($q3) use ($cutoffYear, $cutoffWeek) {
                                    $q3->where('year', '=', $cutoffYear)
                                        ->where('week_number', '<', $cutoffWeek);
                                });
                        });
                },
                'shiftPlanRequests as past_rejected_count' => function ($q) use ($cutoffYear, $cutoffWeek) {
                    $q->where('status', 'rejected')
                        ->where(function ($q2) use ($cutoffYear, $cutoffWeek) {
                            $q2->where('year', '<', $cutoffYear)
                                ->orWhere(function ($q3) use ($cutoffYear, $cutoffWeek) {
                                    $q3->where('year', '=', $cutoffYear)
                                        ->where('week_number', '<', $cutoffWeek);
                                });
                        });
                },
            ])
            ->get();

        return Inertia::render('ShiftPlanRequests/Index', [
            'crafts' => $craftsWithShiftPlans,
        ]);
    }

    public function pastRequests(Craft $craft, Request $request): JsonResponse
    {
        $status = $request->get('status');
        $offset = (int) $request->get('offset', 0);

        $fourWeeksAgo = Carbon::now()->subWeeks(4);
        $cutoffYear = (int) $fourWeeksAgo->format('o');
        $cutoffWeek = (int) $fourWeeksAgo->format('W');

        $requests = ShiftPlanRequest::where('craft_id', $craft->id)
            ->where('status', $status)
            ->where(function ($q) use ($cutoffYear, $cutoffWeek) {
                $q->where('year', '<', $cutoffYear)
                    ->orWhere(function ($q2) use ($cutoffYear, $cutoffWeek) {
                        $q2->where('year', '=', $cutoffYear)
                            ->where('week_number', '<', $cutoffWeek);
                    });
            })
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->skip($offset)
            ->take(10)
            ->get();

        return response()->json(['requests' => $requests]);
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

        $service = app(ShiftPlanRequestService::class);

        // Check-then-create unter Lock auf der Craft-Zeile: zwei gleichzeitige
        // "Alle Schichten festsetzen"-Aufrufe erzeugten sonst zwei pending-Requests
        // für dieselbe KW, auf die sich die Schichten zufällig verteilten.
        $shiftPlanRequest = DB::transaction(function () use ($data, $service) {
            Craft::query()->whereKey($data['craft_id'])->lockForUpdate()->first();

            // 1. Prüfen, ob es bereits ein PENDING-Request für Craft/KW/Jahr gibt
            $shiftPlanRequest = ShiftPlanRequest::query()
                ->where('craft_id', $data['craft_id'])
                ->where('week_number', $data['week_number'])
                ->where('year', $data['year'])
                ->where('status', 'pending')
                ->first();

            // 2. Wenn keines existiert → neues anlegen, sonst vorhandenes nutzen
            if (! $shiftPlanRequest) {
                $shiftPlanRequest = $service->createRequestWithShifts($data, [], true);
            }

            return $shiftPlanRequest;
        });

        // 3. Freie Schichten des Gewerks/der KW an die Anfrage anhängen (Auswahl, Activity-Log,
        //    Pivot-Historie, Workflow-Flags). Gemeinsame Logik in ShiftPlanRequestService, damit
        //    der Backfill-Command exakt dasselbe Verhalten nutzt.
        $service->attachFreeShiftsToRequest($shiftPlanRequest, $user, true);

        // Genehmiger ueber die neue Anfrage benachrichtigen (nur bei Neuanlage,
        // nicht beim Nachreichen weiterer Schichten an eine bestehende Anfrage).
        if ($shiftPlanRequest->wasRecentlyCreated) {
            $this->notifyApproversAboutNewRequest($shiftPlanRequest);
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

        // Alle Schichten, die zu diesem Request gehören (inkl. nachträglich hinzugefügter Schichten)
        $shifts = $this->loadShiftsForRequest($shiftPlanRequest, $start, $end);

        // Alle Worker des Gewerks laden
        $craft = $shiftPlanRequest->craft;
        $craftUsers = $craft->users()->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])->get();
        $craftFreelancers = $craft->freelancers()->get();
        $craftServiceProviders = $craft->serviceProviders()->get();

        $craftUserIds = $craftUsers->pluck('id');
        $craftFreelancerIds = $craftFreelancers->pluck('id');
        $craftServiceProviderIds = $craftServiceProviders->pluck('id');

        // Individual Times für alle Craft-Worker im Wochenzeitraum laden
        $individualTimes = IndividualTime::query()
            ->individualByDateRange($start->toDateString(), $end->toDateString())
            ->where(function ($q) use ($craftUserIds, $craftFreelancerIds, $craftServiceProviderIds) {
                $q->where(function ($q) use ($craftUserIds) {
                    $q->where('timeable_type', User::class)
                      ->whereIn('timeable_id', $craftUserIds);
                })->orWhere(function ($q) use ($craftFreelancerIds) {
                    $q->where('timeable_type', Freelancer::class)
                      ->whereIn('timeable_id', $craftFreelancerIds);
                })->orWhere(function ($q) use ($craftServiceProviderIds) {
                    $q->where('timeable_type', ServiceProvider::class)
                      ->whereIn('timeable_id', $craftServiceProviderIds);
                });
            })
            ->get()
            ->makeVisible(['timeable_type', 'timeable_id'])
            ->map(function ($it) {
                $typeMap = [
                    User::class => 'user',
                    Freelancer::class => 'freelancer',
                    ServiceProvider::class => 'service_provider',
                ];
                $it->timeable_type_short = $typeMap[$it->timeable_type] ?? 'unknown';
                return $it;
            });

        $overviewChanges = app(ShiftPlanRequestService::class)
            ->buildOverviewChangeMarkers($shiftPlanRequest, $start, $end, $shifts);

        return Inertia::render('ShiftPlanRequests/Show', [
            'request' => $shiftPlanRequest,
            'shifts'  => $shifts,
            'days'    => $days,
            'individualTimes' => $individualTimes,
            'overviewChanges' => $overviewChanges,
            'craftWorkers' => [
                'users' => $craftUsers->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->full_name ?? ($u->first_name . ' ' . $u->last_name),
                    'profile_photo_url' => $u->profile_photo_url,
                ]),
                'freelancers' => $craftFreelancers->map(fn ($f) => [
                    'id' => $f->id,
                    'full_name' => $f->full_name ?? $f->name,
                    'profile_photo_url' => $f->profile_photo_url,
                ]),
                'service_providers' => $craftServiceProviders->map(fn ($sp) => [
                    'id' => $sp->id,
                    'name' => $sp->name,
                    'profile_photo_url' => $sp->profile_photo_url,
                ]),
            ],
        ]);
    }

    public function accept(ShiftPlanRequest $shiftPlanRequest): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $this->auth->user();

        // Status-Flip UND Festschreibungs-Loop in EINER Transaktion: schlägt ein
        // Save mitten im Loop fehl, wird auch der Status zurückgerollt (keine
        // "approved"-Anfrage mit halb committeten Schichten).
        $approved = false;
        $response = DB::transaction(function () use ($shiftPlanRequest, $user, &$approved) {
            // Atomarer Status-Flip nur aus 'pending': verhindert Doppel-Genehmigung und
            // Genehmigen nach Ablehnung (auch bei zwei gleichzeitigen Genehmigern —
            // der zweite wartet auf das Row-Lock und sieht dann flipped = 0).
            $flipped = ShiftPlanRequest::query()
                ->whereKey($shiftPlanRequest->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ]);

            if ($flipped === 0) {
                return back()->with('error', __('Shift plan request has already been processed.'));
            }

            $shiftPlanRequest->refresh();

            // commit all shifts in this request
            $shifts = Shift::query()
                ->where('current_request_id', $shiftPlanRequest->id)
                ->get();

            foreach ($shifts as $shift) {
                $shift->workflow_rejection_reason = null;
                $shift->is_committed = true;
                $shift->in_workflow = false;
                // Den Live-Zeiger auf die Anfrage lösen: Sobald eine Schicht genehmigt/festgeschrieben
                // ist, gehört sie keiner aktiven Anfrage mehr an und muss bei einer erneuten
                // "Alle Schichten festsetzen"-Aktion wieder auswählbar sein. Die Historie bleibt über
                // die Pivot-Tabelle shift_plan_request_shifts (requestedShifts) erhalten.
                $shift->current_request_id = null;
                $shift->committing_user_id = $this->auth->id();
                $shift->save();

                DB::table('shift_workers')
                    ->where('shift_id', $shift->id)
                    ->update(['workflow_rejection_reason' => null]);

                activity()
                    ->performedOn($shift)
                    ->causedBy($user)
                    ->useLog('shift')
                    ->event('shift_committed')
                    ->log('Shift committed as part of approved shift plan request');
            }

            $approved = true;

            return back()->with(
                'success',
                __('Shift plan request approved successfully.')
            );
        });

        // Nach erfolgreichem Commit den Antragsteller informieren
        if ($approved) {
            $this->notifyRequesterAboutDecision($shiftPlanRequest->fresh(), true);
        }

        return $response;
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
     *
     * Wichtig: Bevor die Anfrage gelöscht wird, müssen ihre Schichten freigegeben werden
     * (in_workflow = false, current_request_id = null). Andernfalls bleiben die Schichten mit
     * einem Zeiger auf eine nicht mehr existierende Anfrage zurück und wären über die
     * "Alle Schichten festsetzen"-Auswahl nicht mehr erreichbar.
     */
    public function destroy(ShiftPlanRequest $shiftPlanRequest): \Illuminate\Http\RedirectResponse
    {
        // Nur offene Anfragen können zurückgezogen werden — genehmigte/abgelehnte
        // Anfragen sind Historie und dürfen nicht mehr entfernt werden.
        if ($shiftPlanRequest->status !== 'pending') {
            return back()->with('error', __('Shift plan request has already been processed.'));
        }

        DB::transaction(function () use ($shiftPlanRequest): void {
            // Vor dem Bulk-Release loggen: das Query-Builder-Update unten feuert
            // keine Model-Events — ohne diese Einträge wäre das Zurückziehen der
            // Anfrage im Schichtverlauf unsichtbar.
            $causer = $this->auth->user();
            Shift::query()
                ->where('current_request_id', $shiftPlanRequest->id)
                ->get()
                ->each(function (Shift $shift) use ($shiftPlanRequest, $causer): void {
                    activity('shift')
                        ->performedOn($shift)
                        ->causedBy($causer)
                        ->event('workflow_withdrawn')
                        ->tap(function ($activity) use ($shift, $shiftPlanRequest): void {
                            $activity->properties = $activity->properties->merge([
                                'translation_key' => 'Shift released from withdrawn shift plan request from {0}',
                                'translation_key_placeholder_values' => [
                                    optional($shiftPlanRequest->created_at)->format('d.m.Y') ?? '–',
                                ],
                                'context' => 'normal',
                                'shift_id' => $shift->id,
                                'craft_id' => $shift->craft_id,
                                'shift_snapshot' => $shift->toActivitySnapshot(),
                            ]);
                        })
                        ->log('Shift released from withdrawn shift plan request');
                });

            Shift::query()
                ->where('current_request_id', $shiftPlanRequest->id)
                ->update([
                    'in_workflow' => false,
                    'current_request_id' => null,
                    'workflow_rejection_reason' => null,
                ]);

            // Pivot-Historie der Anfrage entfernen und Anfrage löschen
            $shiftPlanRequest->requestedShifts()->detach();
            $shiftPlanRequest->delete();
        });

        return back()->with('success', __('Shift plan request deleted successfully.'));
    }

    public function reject(\Artwork\Modules\Shift\Models\ShiftPlanRequest $shiftPlanRequest, \Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $this->auth->user();

        $payload = $request->validate([
            'global_reason' => ['nullable', 'string'],
            'days' => ['array'],
            'days.*.date' => ['required', 'date'],
            'days.*.reason' => ['nullable', 'string'],

            'shifts' => ['array'],
            'shifts.*.shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'shifts.*.unique_key' => ['required', 'string'],
            'shifts.*.row_type' => ['required', 'string', \Illuminate\Validation\Rule::in(['user', 'freelancer', 'service_provider', 'unassigned'])],
            'shifts.*.row_id' => ['nullable', 'integer'],
            'shifts.*.reason' => ['nullable', 'string'],
        ]);

        // Status-Flip UND Rollback-Loop in EINER Transaktion: schlägt ein Save mitten
        // im Loop fehl, wird auch der Status zurückgerollt (kein 'rejected'-Request
        // mit halb zurückgerollten Schichten).
        $rejected = false;
        $response = DB::transaction(function () use ($shiftPlanRequest, $user, $payload, &$rejected) {
            // Atomarer Status-Flip nur aus 'pending': verhindert Ablehnen nach Genehmigung
            // (Schichten wären schon committed) und doppelte Rollbacks.
            $flipped = ShiftPlanRequest::query()
                ->whereKey($shiftPlanRequest->id)
                ->where('status', 'pending')
                ->update([
                    'rejected_days' => json_encode($payload['days'] ?? []),
                    'rejected_shifts' => json_encode($payload['shifts'] ?? []),
                    'review_comment' => $payload['global_reason'] ?? null,
                    'status' => 'rejected',
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ]);

            if ($flipped === 0) {
                return back()->with('error', __('Shift plan request has already been processed.'));
            }

            $shiftPlanRequest->refresh();

            // Alle Schichten, die zu diesem Request gehören
            $shifts = Shift::query()
                ->where('current_request_id', $shiftPlanRequest->id)
                ->get();

            $selectedEntries = collect($payload['shifts'] ?? []);
            $entriesByShift = $selectedEntries->groupBy('shift_id');

            // Gründe pro Tag
            $dayReasons = collect($payload['days'] ?? [])
                ->pluck('reason', 'date')
                ->map(fn($r) => $this->cleanReason($r))
                ->filter();

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

                $dayReason = $date ? ($dayReasons->get($date) ?? null) : null;
                $globalReason = $this->cleanReason($payload['global_reason'] ?? null);

                // alle Entry-Reasons für diese Schicht
                $entries = $entriesByShift->get($shift->id, collect());

                // ✅ ganz wichtig: alte Pivot-Reasons löschen, sonst schleppst du Altlasten mit
                DB::table('shift_workers')
                    ->where('shift_id', $shift->id)
                    ->update(['workflow_rejection_reason' => null]);

                // Summary fürs Shift-Activitylog (nicht fürs UI)
                $summaryReason = null;

                foreach ($entries as $entry) {
                    $rowType = $entry['row_type'] ?? null;
                    $rowId = isset($entry['row_id']) ? (int)$entry['row_id'] : null;

                    $entryReason = $this->cleanReason($entry['reason'] ?? null);

                    // unassigned → bleibt am Shift (weil es kein Assignment gibt)
                    if ($rowType === 'unassigned' || empty($rowId)) {
                        $summaryReason ??= $entryReason;
                        continue;
                    }

                    $employableClass = $this->rowTypeToEmployableClass($rowType);

                    if (!$employableClass) {
                        continue;
                    }

                    // ✅ pro (shift × employable) setzen (alle rows, falls mehrere qualification-rows existieren)
                    DB::table('shift_workers')
                        ->where('shift_id', $shift->id)
                        ->where('employable_type', $employableClass)
                        ->where('employable_id', $rowId)
                        ->whereNull('deleted_at')
                        ->update(['workflow_rejection_reason' => $entryReason]);

                    $summaryReason ??= $entryReason;
                }

                // wenn nix auf Entry-Ebene da war, nimm Tag/Global
                $summaryReason ??= $dayReason ?? $globalReason;

                // ---------- 2) Initial-Daten aus erster ShiftPlanRequestChange holen ----------
                // Auf die AKTUELLE Anfrage einschränken: Schichten können den Workflow mehrfach
                // durchlaufen (abgelehnte Schichten werden in neue Requests aufgenommen). Ohne den
                // Filter würde auf den _initial-Zustand einer FRÜHEREN Anfrage zurückgerollt und
                // zwischenzeitlich genehmigte Änderungen gingen verloren.
                /** @var ShiftPlanRequestChange|null $firstChange */
                $firstChange = $shift->shiftPlanRequestChanges()
                    ->where('shift_plan_request_id', $shiftPlanRequest->id)
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

                $shift->workflow_rejection_reason = $summaryReason;
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
                    ->useLog('shift')
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

                broadcast(new UpdateShiftInShiftPlan($shift, $shift->room_id ?? $shift->event?->room_id));
            }

            $rejected = true;

            return back()->with('success', __('Shift plan request rejected successfully.'));
        });

        // Nach erfolgreichem Rollback den Antragsteller informieren
        if ($rejected) {
            $this->notifyRequesterAboutDecision($shiftPlanRequest->fresh(), false);
        }

        return $response;
    }

    private function rowTypeToEmployableClass(?string $rowType): ?string
    {
        return match ($rowType) {
            'user' => \Artwork\Modules\User\Models\User::class,
            'freelancer' => \Artwork\Modules\Freelancer\Models\Freelancer::class,
            'service_provider' => \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class,
            default => null,
        };
    }

    private function cleanReason(?string $reason): ?string
    {
        $r = is_string($reason) ? trim($reason) : null;
        return ($r === '') ? null : $r;
    }

    public function changes(Request $request, ?Craft $craft = null): \Inertia\Response
    {
        $allCrafts = Craft::orderBy('name')->get();

        // Filter (all|open|ack) und Seitengröße serverseitig auswerten, damit bei vielen
        // Änderungen nur die jeweils sichtbare Seite geladen, transformiert und übertragen wird.
        $filter = $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'open', 'ack'], true)) {
            $filter = 'all';
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage));

        // Freitext-Suche nach der betroffenen Einheit (Name der zugewiesenen/entfernten
        // Person). Muss serverseitig über die gesamte Datenmenge laufen, nicht nur über
        // die aktuell angezeigte Seite. Gesucht wird über die polymorphe affectedUser-
        // Relation (User/Freelancer/ServiceProvider).
        $search = trim((string) $request->query('search', ''));

        // Intern/Extern-Filter über die betroffene Person (all|internal|external).
        $workerType = $request->query('worker_type', 'all');
        if (! in_array($workerType, ['all', 'internal', 'external'], true)) {
            $workerType = 'all';
        }

        // Basis-Query (Craft-Filter + Suche + Intern/Extern) als Grundlage für Zähler und Liste.
        $baseQuery = $this->committedShiftChangesBaseQuery($craft, $search, $workerType);

        // Zähler unabhängig vom aktiven Filter (für die Badges in den Tabs).
        $totalCount   = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->whereNull('acknowledged_at')->count();

        $changesQuery = (clone $baseQuery)
            ->with([
                // Nur die Felder laden, die das Mapping unten tatsächlich braucht.
                // Wichtig: Shift hat ein automatisches $with (craft, users, freelancer,
                // serviceProvider, committedBy). Beim Laden von shift.craft würden sonst
                // pro Shift tausende User-/Dienstleister-Objekte mitgeladen, was die Seite
                // bei vielen Änderungen unbenutzbar macht. Daher gezielt entfernen.
                'shift' => fn ($q) => $q
                    ->select('id', 'craft_id', 'start_date', 'end_date', 'start', 'end')
                    ->without(['users', 'freelancer', 'serviceProvider', 'committedBy'])
                    ->with('craft:id,abbreviation'),
                // full_name = first_name . ' ' . last_name
                'changedBy:id,first_name,last_name',
            ])
            ->when($filter === 'open', fn ($q) => $q->whereNull('acknowledged_at'))
            ->when($filter === 'ack', fn ($q) => $q->whereNotNull('acknowledged_at'))
            ->orderByDesc('changed_at');

        $paginator = $changesQuery->paginate($perPage)->withQueryString();

        // Nur die Datensätze der aktuellen Seite transformieren (through behält die
        // Paginator-Metadaten/Links bei).
        $paginator->through(function (CommittedShiftChange $change) {
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

                    // Werte aus field_changes können als volle Datetime ("2026-06-20 14:30:00"),
                    // als "HH:MM:SS", als "HH:MM" oder als Platzhalter ("null"/leer) vorliegen.
                    // Einheitlich auf "HH:MM" normalisieren (bzw. null), damit das Datum nicht
                    // doppelt im Label erscheint und Platzhalter den Fallback unten auslösen.
                    $toTime = static function ($value): ?string {
                        if ($value === null || $value === '' || $value === 'null') {
                            return null;
                        }
                        if ($value instanceof \Carbon\Carbon) {
                            return $value->format('H:i');
                        }
                        $str = (string) $value;
                        if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
                            return sprintf('%02d:%s', (int) $m[1], $m[2]);
                        }
                        try {
                            return \Carbon\Carbon::parse($str)->format('H:i');
                        } catch (\Throwable $e) {
                            return null;
                        }
                    };

                    // Zeiten zuerst aus field_changes lesen
                    $beforeStart = $toTime($fieldChanges['start']['old'] ?? null);
                    $beforeEnd   = $toTime($fieldChanges['end']['old']   ?? null);
                    $afterStart  = $toTime($fieldChanges['start']['new'] ?? null);
                    $afterEnd    = $toTime($fieldChanges['end']['new']   ?? null);

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

                    // Fallback-Werte (aus den Shift-Spalten) ebenfalls normalisieren.
                    $beforeStart = $toTime($beforeStart);
                    $beforeEnd   = $toTime($beforeEnd);
                    $afterStart  = $toTime($afterStart);
                    $afterEnd    = $toTime($afterEnd);

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
            'allCrafts'    => $allCrafts,
            'craft'        => $craft,
            'changes'      => $paginator,
            'filter'       => $filter,
            'search'       => $search,
            'workerType'   => $workerType,
            'totalCount'   => $totalCount,
            'pendingCount' => $pendingCount,
        ]);
    }

    /**
     * Basis-Query der Änderungsübersicht (Craft-Filter + Personensuche + Intern/Extern).
     * Wird von changes() (Liste + Zähler) und acknowledgeAll() (Bulk-Genehmigung) geteilt,
     * damit "Alle offenen genehmigen" exakt die Menge trifft, die der Nutzer gerade sieht.
     */
    private function committedShiftChangesBaseQuery(
        ?Craft $craft,
        string $search,
        string $workerType
    ): \Illuminate\Database\Eloquent\Builder {
        return CommittedShiftChange::query()
            ->when($craft, fn ($q) => $q->where('craft_id', $craft->id))
            ->when($search !== '', function ($q) use ($search): void {
                $like = '%' . $search . '%';

                // Vor-/Nachname einzeln sowie als "Vorname Nachname" (User & Freelancer).
                $personName = function ($w) use ($like): void {
                    $w->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]);
                };

                // Hinweis: whereHasMorph ist mit der projektweiten Model-Basisklasse
                // inkompatibel (sie verbietet belongsTo() mit Default-Signatur, die
                // whereHasMorph intern nutzt). Daher pro Typ eine whereIn-Subquery –
                // bleibt vollständig in SQL und nutzt den (affected_user_type,
                // affected_user_id)-Index.
                $q->where(function ($outer) use ($like, $personName): void {
                    $outer->where(function ($w) use ($personName): void {
                        $w->where('affected_user_type', User::class)
                            ->whereIn('affected_user_id', User::query()->where($personName)->select('id'));
                    })->orWhere(function ($w) use ($personName): void {
                        $w->where('affected_user_type', Freelancer::class)
                            ->whereIn('affected_user_id', Freelancer::query()->where($personName)->select('id'));
                    })->orWhere(function ($w) use ($like): void {
                        $w->where('affected_user_type', ServiceProvider::class)
                            ->whereIn('affected_user_id', ServiceProvider::query()
                                ->where('provider_name', 'like', $like)->select('id'));
                    });
                });
            })
            // Intern = normale User; Extern = Freelancer, Dienstleister und User mit
            // "als Freelancer anzeigen" (is_freelancer). Reine Schicht-Änderungen ohne
            // betroffene Person (affected_user_type = null, z.B. Zeitänderungen) betreffen
            // alle Eingeteilten und bleiben deshalb in beiden Ansichten sichtbar.
            ->when($workerType === 'internal', function ($q): void {
                $q->where(function ($outer): void {
                    $outer->whereNull('affected_user_type')
                        ->orWhere(function ($w): void {
                            $w->where('affected_user_type', User::class)
                                ->whereIn('affected_user_id', User::query()
                                    ->where(function ($u): void {
                                        $u->where('is_freelancer', false)
                                            ->orWhereNull('is_freelancer');
                                    })
                                    ->select('id'));
                        });
                });
            })
            ->when($workerType === 'external', function ($q): void {
                $q->where(function ($outer): void {
                    $outer->whereNull('affected_user_type')
                        ->orWhereIn('affected_user_type', [Freelancer::class, ServiceProvider::class])
                        ->orWhere(function ($w): void {
                            $w->where('affected_user_type', User::class)
                                ->whereIn(
                                    'affected_user_id',
                                    User::query()->where('is_freelancer', true)->select('id')
                                );
                        });
                });
            });
    }

    /**
     * Genehmigt alle offenen Änderungen der aktuellen Filterauswahl in EINEM Bulk-Update.
     * Bewusst kein Loop über Models und kein Activity-Log pro Schicht: Bei mehreren
     * tausend offenen Änderungen darf weder der Request kippen noch der Schichtverlauf
     * geflutet werden. Audit-Trail ist acknowledged_by_user_id/acknowledged_at auf jeder
     * Zeile — identisch zur Einzel-Genehmigung, die ebenfalls kein Activity-Log schreibt.
     */
    public function acknowledgeAll(Request $request): \Illuminate\Http\RedirectResponse
    {
        $payload = $request->validate([
            // Bewusst Pflicht: ohne Craft-Scope würde der Button versehentlich die
            // offenen Änderungen ALLER Gewerke genehmigen.
            'craft_id' => ['required', 'integer', 'exists:crafts,id'],
            'search' => ['nullable', 'string'],
            'worker_type' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['all', 'internal', 'external'])],
        ]);

        $craft = Craft::find($payload['craft_id']);
        $search = trim((string) ($payload['search'] ?? ''));
        $workerType = $payload['worker_type'] ?? 'all';

        $updated = $this->committedShiftChangesBaseQuery($craft, $search, $workerType)
            ->whereNull('acknowledged_at')
            ->update([
                'acknowledged_at' => now(),
                'acknowledged_by_user_id' => $this->auth->id(),
            ]);

        return back()->with(
            'success',
            __(':count open changes have been approved.', ['count' => $updated])
        );
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
        $user = User::find($this->auth->id());
        $isAdmin = $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
        $isShiftPlanner = $user->can('can plan shifts');

        $accessibleCraftIds = Craft::query()
            ->where('assignable_by_all', true)
            ->orWhereHas('craftShiftPlaner', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        if ($isAdmin) {
            $shiftPlanRequests = ShiftPlanRequest::with(['craft', 'requestedBy'])
                ->orderByDesc('created_at')
                ->get();
        } else {
            $shiftPlanRequests = ShiftPlanRequest::with(['craft', 'requestedBy'])
                ->where(function ($q) use ($user, $accessibleCraftIds) {
                    $q->where('requested_by_user_id', $user->id)
                      ->orWhereIn('craft_id', $accessibleCraftIds);
                })
                ->orderByDesc('created_at')
                ->get();
        }

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
                    'requested_by_name' => $r->requestedBy?->full_name ?? '',
                ];
            })->values();

            return $craft;
        })->values();

        return Inertia::render('ShiftPlanRequests/MyIndex', [
            'crafts' => $crafts,
            'isPlanner' => $isAdmin || $isShiftPlanner || $accessibleCraftIds->isNotEmpty(),
        ]);
    }

    /**
     * Read-only show for "my" view.
     */
    public function myShow(ShiftPlanRequest $shiftPlanRequest): \Inertia\Response
    {
        // Zugriff analog zur Filterlogik von requests(): eigener Antrag, zuständiges
        // Gewerk (assignable_by_all oder craftShiftPlaner) oder Genehmiger/Admin.
        $user = User::find($this->auth->id());
        $craftIsAccessible = Craft::query()
            ->where('id', $shiftPlanRequest->craft_id)
            ->where(function ($q) use ($user): void {
                $q->where('assignable_by_all', true)
                    ->orWhereHas('craftShiftPlaner', fn ($sq) => $sq->where('user_id', $user->id));
            })
            ->exists();

        abort_unless(
            $shiftPlanRequest->requested_by_user_id === $user->id
            || $craftIsAccessible
            || $user->can('approve-shift-plan-requests'),
            403
        );

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

        // Alle Schichten, die zu diesem Request gehören (inkl. nachträglich hinzugefügter Schichten)
        $shifts = $this->loadShiftsForRequest($shiftPlanRequest, $start, $end);

        // Alle Worker des Gewerks laden (gleich wie in show())
        $craft = $shiftPlanRequest->craft;
        $craftUsers = $craft->users()->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])->get();
        $craftFreelancers = $craft->freelancers()->get();
        $craftServiceProviders = $craft->serviceProviders()->get();

        $craftUserIds = $craftUsers->pluck('id');
        $craftFreelancerIds = $craftFreelancers->pluck('id');
        $craftServiceProviderIds = $craftServiceProviders->pluck('id');

        // Individual Times für alle Craft-Worker im Wochenzeitraum laden
        $individualTimes = IndividualTime::query()
            ->individualByDateRange($start->toDateString(), $end->toDateString())
            ->where(function ($q) use ($craftUserIds, $craftFreelancerIds, $craftServiceProviderIds) {
                $q->where(function ($q) use ($craftUserIds) {
                    $q->where('timeable_type', User::class)
                      ->whereIn('timeable_id', $craftUserIds);
                })->orWhere(function ($q) use ($craftFreelancerIds) {
                    $q->where('timeable_type', Freelancer::class)
                      ->whereIn('timeable_id', $craftFreelancerIds);
                })->orWhere(function ($q) use ($craftServiceProviderIds) {
                    $q->where('timeable_type', ServiceProvider::class)
                      ->whereIn('timeable_id', $craftServiceProviderIds);
                });
            })
            ->get()
            ->makeVisible(['timeable_type', 'timeable_id'])
            ->map(function ($it) {
                $typeMap = [
                    User::class => 'user',
                    Freelancer::class => 'freelancer',
                    ServiceProvider::class => 'service_provider',
                ];
                $it->timeable_type_short = $typeMap[$it->timeable_type] ?? 'unknown';
                return $it;
            });

        $overviewChanges = app(ShiftPlanRequestService::class)
            ->buildOverviewChangeMarkers($shiftPlanRequest, $start, $end, $shifts);

        return Inertia::render('ShiftPlanRequests/Show', [
            'request' => $shiftPlanRequest,
            'shifts'  => $shifts,
            'days'    => $days,
            'individualTimes' => $individualTimes,
            'overviewChanges' => $overviewChanges,
            'craftWorkers' => [
                'users' => $craftUsers->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->full_name ?? ($u->first_name . ' ' . $u->last_name),
                    'profile_photo_url' => $u->profile_photo_url,
                ]),
                'freelancers' => $craftFreelancers->map(fn ($f) => [
                    'id' => $f->id,
                    'full_name' => $f->full_name ?? $f->name,
                    'profile_photo_url' => $f->profile_photo_url,
                ]),
                'service_providers' => $craftServiceProviders->map(fn ($sp) => [
                    'id' => $sp->id,
                    'name' => $sp->name,
                    'profile_photo_url' => $sp->profile_photo_url,
                ]),
            ],
            'isMyRequest' => true,
        ]);
    }

    /**
     * Setzt eine einzelne Änderung eines ShiftPlanRequests gezielt zurück (inkl. Qualifikationen etc.).
     * @param int $requestId
     * @param int $changeId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revertChange(ShiftPlanRequest $shiftPlanRequest, ShiftPlanRequestChange $shiftChange): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = $this->auth->user();

        // Nur Shift-Subject-Changes sind revertierbar: bei Pivot-Changes
        // (ShiftUser & Co.) ist subject_id die PIVOT-ID — Shift::find() würde eine
        // voellig andere Schicht laden und die Feld-Reverts dort anwenden.
        if ($shiftChange->subject_type !== Shift::class) {
            return back()->withErrors([
                'message' => 'Only shift changes can be reverted.',
            ]);
        }

        // Der Change muss zur uebergebenen Anfrage gehoeren — sonst liesse sich ein
        // Change aus Request A ueber die URL von Request B revertieren.
        if ((int) $shiftChange->shift_plan_request_id !== (int) $shiftPlanRequest->id) {
            return back()->withErrors([
                'message' => 'The specified change does not belong to the provided shift plan request.',
            ]);
        }

        // Zugehörige Schicht laden
        $shift = Shift::find($shiftChange->subject_id) ?? null;

        if (! $shift) {
            return back()->withErrors([
                'message' => 'The shift associated with this change could not be found.',
            ]);
        }

        // Sicherheitscheck: Gehört die Schicht zu diesem Request? Der Live-Zeiger
        // current_request_id wird nach Genehmigung gelöst (=null), daher zusätzlich die
        // erhaltene Zuordnung über die Pivot-Tabelle (requestedShifts) prüfen.
        $belongsToRequest = (int) $shift->current_request_id === (int) $shiftPlanRequest->id
            || $shiftPlanRequest->requestedShifts()->where('shift_id', $shift->id)->exists();

        if (! $belongsToRequest) {
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

        // Das Zurücksetzen darf selbst KEINE neuen Änderungs-Einträge erzeugen: Sonst würde der
        // erneute $shift->save() (bzw. die Qualifikations-Saves) über die Observer wieder einen
        // ShiftPlanRequestChange/CommittedShiftChange anlegen und die "abgelehnte" Änderung bliebe
        // als frischer Revert-Eintrag im Verlauf stehen.
        return ShiftChangeRecorder::withoutRecording(fn () => DB::transaction(function () use ($shiftPlanRequest, $shiftChange, $shift, $fieldChanges, $user) {
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

                    // Bestandsdaten: ältere Change-Einträge enthalten Datums-old-Werte
                    // als UTC-ISO-String ("2025-11-14T23:00:00Z" für den lokalen 15.11.).
                    // Ohne Konvertierung würde der Datetime-Cast den UTC-Zeitpunkt in die
                    // DATE-Spalte schreiben und die Schicht auf den Vortag verschieben.
                    if (in_array($field, ['start_date', 'end_date'], true) && !empty($oldValue)) {
                        try {
                            $oldValue = Carbon::parse($oldValue, 'UTC')
                                ->setTimezone(config('app.timezone', 'Europe/Berlin'))
                                ->toDateString();
                        } catch (\Throwable $e) {
                            // im Zweifel Originalwert behalten
                        }
                    }

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

            // Den im selben record()-Aufruf angelegten Post-commit-Eintrag DERSELBEN Bearbeitung
            // entfernen. Bei einer Schicht, die zugleich in_workflow UND is_committed ist, legt
            // ShiftChangeRecorder::record() für eine Bearbeitung sowohl einen ShiftPlanRequestChange
            // (Workflow) als auch einen CommittedShiftChange (Post-commit) mit identischem changed_at
            // an. Beim Ablehnen muss daher auch der Post-commit-Eintrag weg, sonst bleibt die
            // abgelehnte Änderung im Änderungsverlauf (Post-commit-Bereich) sichtbar.
            //
            // Bewusst KEIN neuer "revert"-CommittedShiftChange mehr: Die Ablehnung wird unten als
            // Activity protokolliert (Audit-Trail), soll aber keinen weiteren sichtbaren
            // Änderungseintrag erzeugen.
            if ($shiftChange->changed_at) {
                CommittedShiftChange::query()
                    ->where('shift_id', $shift->id)
                    ->whereNull('acknowledged_at')
                    ->where('changed_at', $shiftChange->changed_at)
                    ->delete();
            }

            // Activity-Log – nur wirklich geänderte Felder loggen
            if (! empty($rollbackFieldChanges)) {
                activity()
                    ->performedOn($shift)
                    ->causedBy($user)
                    ->event('committed_shift_change_reverted')
                    ->useLog('shift')
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
        }));
    }

    /**
     * Benachrichtigt alle hinterlegten Genehmiger (ShiftCommitWorkflowUser) ueber
     * eine neu eingereichte Dienstplananfrage. Das tat frueher nur der entfernte
     * Alt-Store (ShiftCommitWorkflowRequests) — das Live-System lief stumm.
     */
    private function notifyApproversAboutNewRequest(ShiftPlanRequest $shiftPlanRequest): void
    {
        $shiftPlanRequest->loadMissing(['craft', 'requestedBy']);

        [$weekStart, $weekEnd] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $shiftPlanRequest->week_number,
            $shiftPlanRequest->year
        );

        $notificationService = app(NotificationService::class);

        foreach (ShiftCommitWorkflowUser::with('user')->get() as $workflowUser) {
            $userToNotify = $workflowUser->user;
            if (!$userToNotify instanceof User) {
                continue;
            }

            $notificationTitle = __('notification.shift.new_commit_request_title', [], $userToNotify->language);

            $notificationService->setNotificationTo($userToNotify);
            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('green');
            $notificationService->setPriority(2);
            $notificationService->setNotificationConstEnum(
                NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST
            );
            $notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle,
            ]);
            $notificationService->setDescription([
                0 => [
                    'type' => 'text',
                    'title' => __('notification.shift.new_commit_request', [
                        'user' => $shiftPlanRequest->requestedBy?->full_name ?? '',
                        'start_time' => $weekStart->format('d.m.Y'),
                        'end_time' => $weekEnd->format('d.m.Y'),
                    ], $userToNotify->language),
                    'href' => route('shifts.approvals.review'),
                ],
                1 => [
                    'type' => 'link',
                    'title' => __('notification.shift.link_label_new_commit_request', [], $userToNotify->language),
                    'href' => route('shifts.approvals.review'),
                ],
            ]);
            $notificationService->createNotification();
            $notificationService->clearNotificationData();
        }
    }

    /**
     * Benachrichtigt den Antragsteller ueber die Entscheidung (genehmigt/abgelehnt)
     * zu seiner Dienstplananfrage.
     */
    private function notifyRequesterAboutDecision(ShiftPlanRequest $shiftPlanRequest, bool $approved): void
    {
        $requester = User::find($shiftPlanRequest->requested_by_user_id);
        if (!$requester) {
            return;
        }

        $shiftPlanRequest->loadMissing('craft');

        $notificationService = app(NotificationService::class);

        $notificationTitle = __(
            $approved
                ? 'notification.shift.commit_request_approved'
                : 'notification.shift.commit_request_rejected',
            [
                'craft' => $shiftPlanRequest->craft?->name ?? '',
                'week' => $shiftPlanRequest->week_number,
            ],
            $requester->language
        );

        $notificationService->setNotificationTo($requester);
        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon($approved ? 'green' : 'red');
        $notificationService->setPriority(2);
        $notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST
        );
        $notificationService->setBroadcastMessage([
            'id' => Str::uuid()->toString(),
            'type' => $approved ? 'success' : 'error',
            'message' => $notificationTitle,
        ]);
        $notificationService->setDescription([
            0 => [
                'type' => 'link',
                'title' => $notificationTitle,
                'href' => route('shift-plan-requests.my.show', $shiftPlanRequest->id),
            ],
        ]);
        $notificationService->createNotification();
        $notificationService->clearNotificationData();
    }
}
