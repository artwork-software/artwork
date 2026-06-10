<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Services\HelperService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ShiftPlanRequestService
{
    public function __construct(
        protected HelperService $helperService
    ) {
    }

    /**
     * Baut Marker für die Übersicht der Schichtplan-Anfrage:
     *  - "removed": Worker, die eine Schicht hatten, die nachträglich komplett gestrichen wurde, bzw.
     *    die nachträglich aus einer noch existierenden Schicht entfernt wurden → Frontend zeigt eine
     *    durchgestrichene Geister-Zelle ("nachträglich gelöscht").
     *
     * Nachträglich HINZUGEFÜGTE Schichten werden NICHT hier ermittelt, sondern im Controller als
     * echte Schicht-Zeilen geladen (Schichten im Gewerk/der KW, die nicht Teil der ursprünglichen
     * Anfrage sind) und über das Flag `is_subsequently_added` markiert.
     *
     * Quellen:
     *  - committed_shift_changes (post-commit Entfernungen, unbestätigt)
     *  - soft-gelöschte Schichten + ihre soft-gelöschten Zuordnungen (komplett gestrichene Schichten)
     *
     * @param Collection<int,Shift> $liveShifts Bereits geladene, noch existierende Schichten der Anfrage
     *        (inkl. users/freelancer/serviceProvider).
     * @return array{removed: array<int,array<string,mixed>>}
     */
    public function buildOverviewChangeMarkers(
        ShiftPlanRequest $request,
        \Carbon\Carbon $start,
        \Carbon\Carbon $end,
        Collection $liveShifts
    ): array {
        $startYmd = $start->toDateString();
        $endYmd   = $end->toDateString();

        $typeShort = [
            User::class            => 'user',
            Freelancer::class      => 'freelancer',
            ServiceProvider::class => 'service_provider',
        ];

        // Aktuell bestehende Zuordnungen als Set "${shiftId}-${type}-${id}".
        $current = [];
        foreach ($liveShifts as $shift) {
            foreach ($shift->users as $u) {
                $current[$shift->id . '-user-' . $u->id] = true;
            }
            foreach ($shift->freelancer as $f) {
                $current[$shift->id . '-freelancer-' . $f->id] = true;
            }
            foreach ($shift->serviceProvider as $s) {
                $current[$shift->id . '-service_provider-' . $s->id] = true;
            }
        }

        $removed = [];

        // 1) Post-commit Entfernungen (nachträgliche, noch unbestätigte Änderungen)
        $removedTypes = [
            'user_removed_from_shift',
            'freelancer_removed_from_shift',
            'service_provider_removed_from_shift',
        ];

        $committedChanges = CommittedShiftChange::query()
            ->where('craft_id', $request->craft_id)
            ->whereNull('acknowledged_at')
            ->whereIn('change_type', $removedTypes)
            ->get();

        foreach ($committedChanges as $change) {
            $assignment = data_get($change->field_changes, 'assignment');
            if (! is_array($assignment)) {
                continue;
            }

            $short = $typeShort[$change->affected_user_type] ?? 'user';
            $workerId = $change->affected_user_id ?? data_get($assignment, 'user_id');
            if (! $workerId) {
                continue;
            }

            $date = data_get($assignment, 'start_date'); // Y-m-d
            if (! $date || $date < $startYmd || $date > $endYmd) {
                continue;
            }

            $uniqueKey = $change->shift_id . '-' . $short . '-' . $workerId;

            // Entfernung: Geister-Zelle nur, wenn der Worker aktuell NICHT (wieder) auf der Schicht ist.
            if ($change->shift_id && isset($current[$uniqueKey])) {
                continue;
            }

            $removed[] = [
                'row_key'       => $short . '-' . $workerId,
                'type'          => $short,
                'id'            => (int) $workerId,
                'name'          => data_get($assignment, 'user_name'),
                'date'          => $date,
                'start'         => data_get($assignment, 'start_time'),
                'end'           => data_get($assignment, 'end_time'),
                'qualification' => data_get($assignment, 'shift_qualification_name'),
                'source'        => 'post_commit',
                'reason'        => 'removed',
                'shift_id'      => $change->shift_id,
            ];
        }

        // 2) Komplett gestrichene (soft-gelöschte) Schichten im Gewerk/Zeitraum inkl. ihrer Worker.
        $trashedShifts = Shift::onlyTrashed()
            ->where('craft_id', $request->craft_id)
            ->whereBetween('start_date', [$startYmd, $endYmd])
            ->get(['id', 'start_date', 'end_date', 'start', 'end']);

        if ($trashedShifts->isNotEmpty()) {
            $trashedById = $trashedShifts->keyBy('id');

            $trashedWorkers = ShiftWorker::withTrashed()
                ->whereNotNull('deleted_at')
                ->whereIn('shift_id', $trashedShifts->pluck('id')->all())
                ->get();

            $names = $this->resolveWorkerNames($trashedWorkers, $typeShort);

            foreach ($trashedWorkers as $worker) {
                $shift = $trashedById->get($worker->shift_id);
                if (! $shift) {
                    continue;
                }

                $short = $typeShort[$worker->employable_type] ?? 'user';
                $date = optional($shift->start_date)->toDateString();
                if (! $date) {
                    continue;
                }

                $removed[] = [
                    'row_key'       => $short . '-' . $worker->employable_id,
                    'type'          => $short,
                    'id'            => (int) $worker->employable_id,
                    'name'          => $names[$short . '-' . $worker->employable_id] ?? null,
                    'date'          => $date,
                    'start'         => $worker->start_time
                        ? Carbon::parse($worker->start_time)->format('H:i')
                        : (string) $shift->start,
                    'end'           => $worker->end_time
                        ? Carbon::parse($worker->end_time)->format('H:i')
                        : (string) $shift->end,
                    'qualification' => null,
                    'source'        => 'shift_deleted',
                    'reason'        => 'shift_deleted',
                    'shift_id'      => $worker->shift_id,
                ];
            }
        }

        return [
            'removed' => $removed,
        ];
    }

    /**
     * Löst die Anzeigenamen für (soft-gelöschte) ShiftWorker auf, gruppiert nach Typ.
     *
     * @param Collection<int,ShiftWorker> $workers
     * @param array<class-string,string> $typeShort
     * @return array<string,string> key: "${short}-${id}" => Name
     */
    protected function resolveWorkerNames(Collection $workers, array $typeShort): array
    {
        $names = [];

        $byType = $workers->groupBy('employable_type');
        foreach ($byType as $type => $group) {
            $short = $typeShort[$type] ?? null;
            if (! $short || ! class_exists($type)) {
                continue;
            }

            $ids = $group->pluck('employable_id')->unique()->all();
            /** @var Collection<int,\Illuminate\Database\Eloquent\Model> $models */
            $models = $type::query()->whereIn('id', $ids)->get();

            foreach ($models as $model) {
                $name = $model->full_name
                    ?? ($model->name ?? trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')));
                $names[$short . '-' . $model->id] = $name !== '' ? $name : null;
            }
        }

        return $names;
    }

    /**
     * Create a ShiftPlanRequest and attach the provided shifts into the historical pivot table.
     *
     * @param array $data  // attributes for ShiftPlanRequest (craft_id, week_number, year, status, requested_by_user_id...)
     * @param array<int> $shiftIds
     * @param bool $withSnapshot // if true, store a small snapshot per shift
     * @return ShiftPlanRequest
     */
    public function createRequestWithShifts(array $data, array $shiftIds, bool $withSnapshot = true): ShiftPlanRequest
    {
        // Create the request
        /** @var ShiftPlanRequest $request */
        $request = ShiftPlanRequest::create(Arr::only($data, [
            'craft_id',
            'week_number',
            'year',
            'status',
            'requested_by_user_id',
            'requested_at',
            'reviewed_by_user_id',
            'reviewed_at',
            'review_comment',
        ]));

        if (empty($shiftIds)) {
            return $request;
        }

        $shifts = Shift::whereIn('id', $shiftIds)->get();

        $attachPayload = [];
        foreach ($shifts as $shift) {
            $payload = [];

            if ($withSnapshot) {
                $payload['snapshot'] = json_encode([
                    'start_date' => $shift->start_date?->toDateString() ?? null,
                    'end_date' => $shift->end_date?->toDateString() ?? null,
                    'start' => (string)$shift->start,
                    'end' => (string)$shift->end,
                    'craft_id' => $shift->craft_id,
                    'shift_id' => $shift->id,
                ], JSON_THROW_ON_ERROR);
            }

            $attachPayload[$shift->id] = $payload;
        }

        $request->requestedShifts()->attach($attachPayload);

        return $request;
    }

    /**
     * Attach shifts to an existing ShiftPlanRequest (idempotent).
     * Uses syncWithoutDetaching to avoid duplicate entries.
     *
     * @param ShiftPlanRequest $request
     * @param array<int> $shiftIds
     * @param bool $withSnapshot
     * @return void
     */
    public function attachShiftsToRequest(ShiftPlanRequest $request, array $shiftIds, bool $withSnapshot = true): void
    {
        if (empty($shiftIds)) {
            return;
        }

        $shifts = Shift::whereIn('id', $shiftIds)->get();

        $attachPayload = [];
        foreach ($shifts as $shift) {
            $payload = [];

            if ($withSnapshot) {
                $payload['snapshot'] = json_encode([
                    'start_date' => $shift->start_date?->toDateString() ?? null,
                    'end_date' => $shift->end_date?->toDateString() ?? null,
                    'start' => (string)$shift->start,
                    'end' => (string)$shift->end,
                    'craft_id' => $shift->craft_id,
                    'shift_id' => $shift->id,
                ], JSON_THROW_ON_ERROR);
            }

            $attachPayload[$shift->id] = $payload;

            $roomId = $shift->room_id ?? $shift->event?->room_id;
            if ($roomId !== null) {
                broadcast(new UpdateShiftInShiftPlan($shift, $roomId));
            }
        }

        // idempotent attach
        $request->requestedShifts()->syncWithoutDetaching($attachPayload);
    }

    /**
     * Hängt alle aktuell "freien" Schichten des Gewerks/der KW an eine bestehende Anfrage an
     * (Activity-Log, Pivot-Historie, Workflow-Flags) – exakt die Logik hinter
     * "Alle Schichten festsetzen". Wird sowohl vom Controller (store) als auch vom
     * Backfill-Command genutzt, damit es nur EINE Quelle der Wahrheit gibt.
     *
     * Freie Schichten = keine Zuordnung, verwaiste Zuordnung oder Zuordnung zu einer bereits
     * abgeschlossenen (rejected/approved) Anfrage. Bereits in einer aktiven (pending) Anfrage
     * befindliche Schichten werden NICHT erneut angehängt.
     *
     * @return array<int> Die IDs der neu angehängten Schichten.
     */
    /**
     * Query der aktuell "freien" Schichten für das Gewerk/die KW einer Anfrage.
     * Freie Schicht = keine Zuordnung, verwaiste Zuordnung oder Zuordnung zu einer bereits
     * abgeschlossenen (rejected/approved) Anfrage. Source of Truth ist der Anfrage-Status,
     * NICHT das denormalisierte `in_workflow`-Flag.
     */
    public function freeShiftsForRequestQuery(ShiftPlanRequest $request): \Illuminate\Database\Eloquent\Builder
    {
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $request->week_number,
            $request->year
        );

        return Shift::query()
            ->where('craft_id', $request->craft_id)
            ->startAndEndDateOverlap($start->toDateString(), $end->toDateString())
            ->where(function ($q): void {
                $q
                    ->whereNull('current_request_id')
                    ->orWhereDoesntHave('currentRequest')
                    ->orWhereHas('currentRequest', function ($sub): void {
                        $sub->whereIn('status', ['rejected', 'approved']);
                    });
            });
    }

    public function attachFreeShiftsToRequest(
        ShiftPlanRequest $request,
        ?User $causer = null,
        bool $broadcast = true
    ): array {
        $shiftsQuery = $this->freeShiftsForRequestQuery($request);

        $shifts = $shiftsQuery
            ->with('currentRequest') // wichtig für History-Log
            ->get();

        $shiftIdsToAttach = [];

        foreach ($shifts as $shift) {
            $previousRequest = null;
            if ($shift->currentRequest && $shift->currentRequest->status === 'rejected') {
                $previousRequest = $shift->currentRequest;
            }

            $activity = activity()
                ->performedOn($shift)
                ->useLog('shift')
                ->event('shift_added_to_request')
                ->withProperties([
                    'old' => [
                        'in_workflow' => $shift->in_workflow,
                    ],
                    'attributes' => [
                        'in_workflow' => true,
                    ],
                    'shift_plan_request_id' => $request->id,
                    'previous_shift_plan_request_id' => $previousRequest?->id,
                ]);

            if ($causer) {
                $activity->causedBy($causer);
            }

            if ($previousRequest) {
                $activity->tap(function (Activity $activity) use ($previousRequest, $request): void {
                    $props = $activity->properties ?? collect();

                    $props = $props->merge([
                        'translation_key' => 'Shift rejected in request from {0} with reason "{1}", now added to {2}',
                        'translation_key_placeholder_values' => [
                            optional($previousRequest->created_at)->format('d.m.Y'),
                            $previousRequest->rejection_reason ?? '',
                            optional($request->created_at)->format('d.m.Y'),
                        ],
                    ]);

                    $activity->properties = $props;
                });
            }

            $activity->log('Shift added to shift plan request');

            $shiftIdsToAttach[] = $shift->id;
        }

        if (! empty($shiftIdsToAttach)) {
            // Pivot-Historie (idempotent). attachShiftsToRequest broadcastet bereits selbst.
            $this->attachShiftsToRequest($request, $shiftIdsToAttach, true);

            $shiftsQuery->update([
                'current_request_id' => $request->id,
                'in_workflow' => true,
                'workflow_rejection_reason' => null,
            ]);

            DB::table('shift_workers')
                ->whereIn('shift_id', $shiftIdsToAttach)
                ->update(['workflow_rejection_reason' => null]);
        }

        if ($broadcast) {
            foreach ($shifts as $shift) {
                $freshedShift = $shift->fresh();
                $roomId = $freshedShift->room_id ?? $freshedShift->event?->room_id;
                if ($roomId !== null) {
                    broadcast(new UpdateShiftInShiftPlan($freshedShift, $roomId));
                }
            }
        }

        return $shiftIdsToAttach;
    }
}
