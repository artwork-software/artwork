<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\AppApi\Enums\WorkerType;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

class AppShiftPlanService
{
    public function __construct(
        private readonly EventService $eventService,
    ) {
    }

    /**
     * Days of the user's own shift plan in the app contract shape — one list
     * entry per calendar day. Wraps the web Einsatzplan's day aggregation and
     * slims its heavy model payloads down to the fields the app renders; the
     * app validates this shape on the device, so keys are load-bearing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDays(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $days = $this->eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'user',
            $startDate,
            $endDate,
        );

        return array_values(array_map(
            fn (array $day): array => $this->mapDay($day, $user),
            $days,
        ));
    }

    /**
     * One shift of the user's own plan, for deeplinks — the app does not have
     * to download a whole week to show a single shift. Null when the shift is
     * not on the user's plan.
     *
     * @return array{date: string, shift: array<string, mixed>}|null
     */
    public function shiftOf(User $user, Shift $shift): ?array
    {
        if (!$shift->users()->whereKey($user->id)->exists()) {
            return null;
        }

        $day = Carbon::parse($shift->start_date);

        foreach ($this->getDays($user, $day->copy()->startOfDay(), $day->copy()->endOfDay()) as $planDay) {
            foreach ($planDay['shifts'] as $planShift) {
                if ((int) $planShift['id'] === $shift->id) {
                    return ['date' => $planDay['date'], 'shift' => $planShift];
                }
            }
        }

        return null;
    }

    /**
     * Range totals over already-mapped days, so the app never sums time
     * strings itself.
     *
     * @param array<int, array<string, mixed>> $days
     * @return array{work_time: string, shift_count: int}
     */
    public function totals(array $days): array
    {
        $minutes = 0;
        $shiftCount = 0;
        foreach ($days as $day) {
            $minutes += $this->clockToMinutes((string) $day['total_work_time']);
            $shiftCount += count($day['shifts']);
        }

        return [
            'work_time' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
            'shift_count' => $shiftCount,
        ];
    }

    /**
     * Core emits working times in mixed formats (decimal hours or H:mm) —
     * the wire promises zero-padded "HH:mm" everywhere.
     */
    private function clockFormat(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{1,3}:\d{2}$/', $value) === 1) {
            return strlen($value) === 4 ? '0' . $value : $value;
        }

        $minutes = (int) round(((float) $value) * 60);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function clockToMinutes(string $clock): int
    {
        [$hours, $minutes] = array_pad(explode(':', $clock, 2), 2, '0');

        return ((int) $hours) * 60 + (int) $minutes;
    }

    /**
     * @param array<string, mixed> $day
     * @return array<string, mixed>
     */
    private function mapDay(array $day, User $user): array
    {
        return [
            'date' => $day['date'],
            'total_work_time' => $this->clockFormat($day['totalWorkTime']),
            'total_break_time' => $this->clockFormat($day['totalBreakTime']),
            'shifts' => array_map(
                fn (array $shift): array => $this->mapShift($shift, $user),
                $day['shifts'],
            ),
            'individual_times' => array_map(static fn (array $individualTime): array => [
                'id' => $individualTime['id'],
                'title' => $individualTime['title'],
                'start_time' => $individualTime['start_time'],
                'end_time' => $individualTime['end_time'],
                'full_day' => $individualTime['full_day'],
            ], $day['individualTimes']),
            'day_services' => array_map(static fn (array $dayService): array => [
                'id' => $dayService['id'],
                'name' => $dayService['name'],
                'icon' => $dayService['icon'],
                'hex_color' => $dayService['hex_color'],
            ], $day['dayServices']),
            'holidays' => array_map(static fn (array $holiday): array => [
                'id' => $holiday['id'],
                'name' => $holiday['name'],
            ], $day['holidays']),
            'comments' => array_map(static fn (array $comment): array => [
                'id' => $comment['id'],
                'comment' => $comment['comment'],
                'date' => $comment['date'],
            ], $day['comments']),
        ];
    }

    /**
     * @param array<string, mixed> $shift
     * @return array<string, mixed>
     */
    private function mapShift(array $shift, User $user): array
    {
        $event = $shift['event'] ?? null;
        $project = $shift['project'] ?? null;
        $room = $shift['room'] ?? null;
        $craft = $shift['craft'] ?? null;

        return [
            'id' => $shift['id'],
            'name' => $shift['name'],
            'start' => $shift['start'],
            'end' => $shift['end'],
            'break_minutes' => (int) $shift['break_minutes'],
            'description' => $shift['description'],
            'is_committed' => $shift['is_committed'],
            'in_workflow' => $shift['in_workflow'],
            'planned_working_hours' => $this->clockFormat($shift['plannedWorkingHours']),
            'craft' => $this->mapCraft($craft),
            'event' => $this->mapEvent($event),
            'project' => $this->mapProject($project),
            'room' => $this->mapRoom($room),
            'colleagues' => $this->mapColleagues($shift['workers'], $user),
        ];
    }

    /**
     * @return array{id: int, name: string, abbreviation: string, color: string}|null
     */
    private function mapCraft(mixed $craft): ?array
    {
        if (!$craft instanceof Craft) {
            return null;
        }

        return [
            'id' => $craft->id,
            'name' => $craft->name,
            'abbreviation' => $craft->abbreviation,
            'color' => $craft->color,
        ];
    }

    /**
     * @param array{id: int, eventName: string|null}|null $event the slim event row from EventService
     * @return array{id: int, name: string}|null
     */
    private function mapEvent(?array $event): ?array
    {
        if ($event === null) {
            return null;
        }

        return [
            'id' => $event['id'],
            'name' => $event['eventName'] ?? '',
        ];
    }

    /**
     * @param array{id: int, name: string}|null $project the slim project row from EventService
     * @return array{id: int, name: string}|null
     */
    private function mapProject(?array $project): ?array
    {
        if ($project === null) {
            return null;
        }

        return ['id' => $project['id'], 'name' => $project['name']];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function mapRoom(mixed $room): ?array
    {
        if (!$room instanceof Room) {
            return null;
        }

        return ['id' => $room->id, 'name' => $room->name];
    }

    /**
     * The unified polymorphic worker list minus the requesting user.
     *
     * @param array<int, User|Freelancer|ServiceProvider> $workers
     * @return array<int, array{id: int, type: string, name: string}>
     */
    private function mapColleagues(array $workers, User $user): array
    {
        $colleagues = [];

        foreach ($workers as $worker) {
            if ($worker instanceof User && $worker->id === $user->id) {
                continue;
            }

            $colleagues[] = [
                'id' => $worker->id,
                'type' => WorkerType::of($worker)->value,
                'name' => WorkerType::displayName($worker),
            ];
        }

        return $colleagues;
    }
}
