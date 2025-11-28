<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ShiftPlanRequestService
{
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
            broadcast(new UpdateShiftInShiftPlan($shift, $shift->room_id ?? $shift->event->room_id));
        }

        // idempotent attach
        $request->requestedShifts()->syncWithoutDetaching($attachPayload);
    }
}
