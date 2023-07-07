<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use function React\Promise\reject;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Event $event)
    {
        $event->shifts()->create($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function show(Shift $shift)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function edit(Shift $shift)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Shift $shift)
    {
        $shift->update($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        return Redirect::route('shifts.plan')->with('success', 'Shift updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function destroy(Shift $shift)
    {
        //
    }

    protected function calculateShiftCollision(Shift $shift, array $eventIds, int $user_id = null, int $freelancer_id = null, int $service_provider_id = null): Collection
    {
        $shift->load('event');

        /** @var Event $event */
        $event = $shift->event;
        $startDate = $event->start_time;
        $endDate = $event->end_time;

       return Event::query()
            ->whereIntegerInRaw('id', $eventIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orWhere(function($query) use ($endDate, $startDate, $eventIds) {
                $query->whereBetween('end_time', [$startDate, $endDate])
                    ->whereIntegerInRaw('id', $eventIds);
            })
            ->orWhere(function($query) use ($endDate, $startDate, $eventIds) {
                $query->where('start_time', '>=', $startDate)
                    ->where('end_time', '<=', $endDate)
                    ->whereIntegerInRaw('id', $eventIds);
            })
            ->orWhere(function($query) use ($endDate, $startDate, $eventIds) {
                $query->where('start_time', '<=', $startDate)
                    ->where('end_time', '>=', $endDate)
                    ->whereIntegerInRaw('id', $eventIds);
            })
            ->with('shifts')
            ->get()
            ->pluck('shifts')
            ->flatten()
            ->reject(
                fn(Shift $currentShift) =>
                    $currentShift->id === $shift->id // remove the shift I am attaching to
                    || $currentShift->start > $shift->end //remove shifts that start after the shift I am attaching to has ended
                    || $currentShift->end < $shift->start//remove shifts ended before the shift I am attaching to has started
                    || $user_id && $currentShift->users->doesntContain($user_id)
                    || $freelancer_id && $currentShift->freelancer->doesntContain($freelancer_id)
                    || $service_provider_id && $currentShift->service_provider->doesntContain($service_provider_id)
            )
            ->pluck('id');
    }

    public function addShiftUser(Shift $shift, User $user): void
    {
        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, user_id: $user->id);
        $collideCount = $collidingShiftIds->count();

        if($collideCount > 0) {
            $user->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        $shift->users()->attach($user->id, ['shift_count' => $collideCount + 1]);
    }

    public function addShiftMaster(Request $request, Shift $shift): void
    {
        $shift->users()->attach($request->user_id, ['is_master' => true]);
    }


    public function addShiftFreelancer(Shift $shift, Freelancer $freelancer): void
    {
        $eventIdsOfUserShifts = $freelancer->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, freelancer_id: $freelancer->id);
        $collideCount = $collidingShiftIds->count();

        if($collideCount > 0) {
            $freelancer->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        $shift->freelancer()->attach($freelancer->id, ['shift_count' => $collideCount + 1]);
    }

    public function addShiftFreelancerMaster(Request $request, Shift $shift): void
    {
        $shift->freelancer()->attach($request->freelancer_id, ['is_master' => true]);
    }



    public function addShiftProviderMaster(Request $request, Shift $shift): void
    {
        $shift->service_provider()->attach($request->service_provider_id, ['is_master' => true]);
    }

    public function removeUser(Shift $shift, User $user): void
    {
        $shift->users()->detach($user->id);

        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, user_id: $user->id);
        $collideCount = $collidingShiftIds->count();

        $user->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );
    }

    public function removeFreelancer(Shift $shift, Freelancer $freelancer): void
    {
        $shift->freelancer()->detach($freelancer->id);

        $eventIdsOfUserShifts = $freelancer->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, freelancer_id: $freelancer->id);
        $collideCount = $collidingShiftIds->count();

        $freelancer->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );
    }

    public function removeProvider(Shift $shift, ServiceProvider $serviceProvider): void
    {
        $shift->service_provider()->detach($serviceProvider->id);

        $eventIdsOfUserShifts = $serviceProvider->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, service_provider_id: $serviceProvider->id);
        $collideCount = $collidingShiftIds->count();

        $serviceProvider->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );
    }

    public function addShiftProvider(Shift $shift, ServiceProvider $serviceProvider): void
    {

        $eventIdsOfUserShifts = $serviceProvider->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, service_provider_id: $serviceProvider->id);
        $collideCount = $collidingShiftIds->count();

        if($collideCount > 0) {
            $serviceProvider->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        $shift->service_provider()->attach($serviceProvider->id, ['shift_count' => $collideCount + 1]);
    }
}
