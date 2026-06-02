<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Services\HelperService;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ShiftPlanRequestService
{
    public function __construct(
        protected HelperService $helperService
    ) {
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
