<?php

namespace Artwork\Modules\Shift\Serializers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftWorkerAvailability;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

readonly class ShiftListViewSerializer
{
    public function serializeShift(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'start' => (string) $shift->start,
            'end' => (string) $shift->end,
            'start_date' => $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : '',
            'end_date' => $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : '',
            'break_minutes' => $shift->break_minutes,
            'description' => $shift->description,
            'craft_id' => $shift->craft_id,
            'room_id' => $shift->room_id,
            'event_id' => $shift->event_id,
            'is_committed' => $shift->is_committed,
            'shift_group_id' => $shift->shift_group_id,
            'craft' => $shift->craft ? [
                'id' => $shift->craft->id,
                'name' => $shift->craft->name,
                'abbreviation' => $shift->craft->abbreviation,
                'color' => $shift->craft->color,
            ] : null,
            'shift_group' => $shift->shiftGroup ? [
                'id' => $shift->shiftGroup->id,
                'name' => $shift->shiftGroup->name,
            ] : null,
            'project' => $shift->project ? [
                'id' => $shift->project->id,
                'name' => $shift->project->name,
            ] : null,
            'event' => $shift->event ? [
                'id' => $shift->event->id,
                'project' => $shift->event->project ? [
                    'id' => $shift->event->project->id,
                    'name' => $shift->event->project->name,
                ] : null,
            ] : null,
            'workers' => [
                ...$this->serializeListViewWorkers($shift->users, 'user', $shift),
                ...$this->serializeListViewWorkers($shift->freelancer, 'freelancer', $shift),
                ...$this->serializeListViewWorkers($shift->serviceProvider, 'service_provider', $shift),
            ],
            'shifts_qualifications' => $shift->shiftsQualifications->map(fn ($sq) => [
                'id' => $sq->id,
                'shift_id' => $sq->shift_id,
                'shift_qualification_id' => $sq->shift_qualification_id,
                'value' => $sq->value,
                'overbooked_value' => $sq->overbooked_value,
            ])->values()->all(),
            'globalQualifications' => $shift->globalQualifications->map(fn ($gq) => [
                'id' => $gq->id,
                'pivot' => ['quantity' => $gq->pivot->quantity ?? 0],
            ])->values()->all(),
        ];
    }

    public function serializeListViewWorkers(
        EloquentCollection|SupportCollection|null $workers,
        string $type,
        ?Shift $shift = null
    ): array {
        if ($workers === null || $workers->isEmpty()) {
            return [];
        }

        return $workers->map(function ($worker) use ($type, $shift) {
            $data = [
                'id' => $worker->id,
                'type' => $type,
                'pivot' => $worker->pivot ? [
                    'shift_qualification_id' => $worker->pivot->shift_qualification_id ?? null,
                    'is_overbooked' => (bool) ($worker->pivot->is_overbooked ?? false),
                ] : null,
                'is_unavailable' => $shift !== null
                    && ShiftWorkerAvailability::isWorkerUnavailable($shift, $worker),
            ];

            if ($type === 'service_provider') {
                $data['provider_name'] = $worker->provider_name;
                $data['name'] = $worker->provider_name;
            } else {
                $data['first_name'] = $worker->first_name;
                $data['last_name'] = $worker->last_name;
                $data['name'] = trim($worker->first_name . ' ' . $worker->last_name);
            }

            return $data;
        })->values()->all();
    }

    public function serializeListViewEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'eventName' => $event->eventName,
            'description' => $event->description,
            'allDay' => (bool) $event->allDay,
            'start_time' => (string) $event->start_time,
            'end_time' => (string) $event->end_time,
            'event_type' => $event->event_type ? [
                'id' => $event->event_type->id,
                'name' => $event->event_type->name,
                'abbreviation' => $event->event_type->abbreviation,
                'hex_code' => $event->event_type->hex_code,
            ] : null,
            'project' => $event->project ? [
                'id' => $event->project->id,
                'name' => $event->project->name,
            ] : null,
        ];
    }
}
