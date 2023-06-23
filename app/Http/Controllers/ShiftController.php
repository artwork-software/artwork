<?php

namespace App\Http\Controllers;

use App\Models\Event;
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

    protected function calculateShiftCollision(User $user, Shift $shift): Collection
    {
        $user->load('shifts.event');
        $shift->load('event');

        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();

        /** @var Event $event */
        $event = $shift->event;
        $startDate = $event->start_time;
        $endDate = $event->end_time;

       return Event::query()
            ->whereIntegerInRaw('id', $eventIdsOfUserShifts)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orWhere(function($query) use ($endDate, $startDate, $eventIdsOfUserShifts) {
                $query->whereBetween('end_time', [$startDate, $endDate])
                    ->whereIntegerInRaw('id', $eventIdsOfUserShifts);
            })
            ->orWhere(function($query) use ($endDate, $startDate, $eventIdsOfUserShifts) {
                $query->where('start_time', '>=', $startDate)
                    ->where('end_time', '<=', $endDate)
                    ->whereIntegerInRaw('id', $eventIdsOfUserShifts);
            })
            ->orWhere(function($query) use ($endDate, $startDate, $eventIdsOfUserShifts) {
                $query->where('start_time', '<=', $startDate)
                    ->where('end_time', '>=', $endDate)
                    ->whereIntegerInRaw('id', $eventIdsOfUserShifts);
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
                    || $currentShift->users->doesntContain($user->id))
            ->pluck('id');
    }

    public function addShiftUser(Shift $shift, User $user): void
    {
        $collidingShiftIds = $this->calculateShiftCollision($user, $shift);
        $collideCount = $collidingShiftIds->count();

        $percentage = 1 / ($collideCount + 1);

        if($collideCount > 0) {
            $user->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_percentage' => $percentage]
            );
        }

        $shift->users()->attach($user->id, ['shift_percentage' => $percentage]);
    }

    public function addShiftMaster(Request $request, Shift $shift): void
    {
        $shift->users()->attach($request->user_id, ['is_master' => true]);
    }


    public function addShiftFreelancer(Request $request, Shift $shift): void
    {
        $shift->freelancer()->attach($request->freelancer_id);
    }

    public function addShiftFreelancerMaster(Request $request, Shift $shift): void
    {
        $shift->freelancer()->attach($request->freelancer_id, ['is_master' => true]);
    }

    public function addShiftProvider(Request $request, Shift $shift): void
    {
        $shift->service_provider()->attach($request->service_provider_id);
    }

    public function addShiftProviderMaster(Request $request, Shift $shift): void
    {
        $shift->service_provider()->attach($request->service_provider_id, ['is_master' => true]);
    }

    public function removeUser(Shift $shift, User $user): void
    {
        $shift->users()->detach($user->id);

        $collidingShiftIds = $this->calculateShiftCollision($user, $shift);
        $collideCount = $collidingShiftIds->count();

        $user->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_percentage' => 1 / (
                    $collideCount > 0
                        ? $collideCount
                        : 1
                    )
            ]
        );
    }

    public function removeFreelancer(Request $request, Shift $shift): void
    {
        $shift->freelancer()->detach($request->freelancer_id);
    }

    public function removeProvider(Request $request, Shift $shift): void
    {
        $shift->service_provider()->detach($request->service_provider_id);
    }
}
