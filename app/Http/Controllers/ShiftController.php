<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\Shift;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use function React\Promise\reject;

class ShiftController extends Controller
{

    protected ?NewHistoryService $history = null;

    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\Shift');
    }

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
        $shift = $event->shifts()->create($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        if($request->changeAll){
            $seriesEvents = Event::where('series_id', $event->series_id)->get();

            foreach ($seriesEvents as $seriesEvent){
                if($seriesEvent->id != $event->id){
                    $seriesEvent->shifts()->create($request->only([
                        'start',
                        'end',
                        'break_minutes',
                        'craft_id',
                        'number_employees',
                        'number_masters',
                        'description',
                    ]));
                }
            }
        }

        $this->history->createHistory($shift->id, 'Schicht von Event '. $event->eventName .' wurde erstellt', 'shift');
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
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Schicht von Event '. $event->eventName .' wurde bearbeitet', 'shift');
        }
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

    public function updateShift(Request $request, Shift $shift)
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Schicht von Event '. $event->eventName .' wurde bearbeitet', 'shift');
        }
        $shift->update($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Shift  $shift
     */
    public function destroy(Shift $shift)
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Schicht von Event '. $event->eventName .' wurde gelöscht', 'shift');
        }
        $shift->delete();
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
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') hinzugefügt', 'shift');
        }
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

    public function addShiftMaster(Request $request, Shift $shift, User $user): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde  zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') als Meister hinzugefügt', 'shift');
        }
        $shift->users()->attach($user->id, ['is_master' => true]);
    }


    public function addShiftFreelancer(Shift $shift, Freelancer $freelancer): void
    {

        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Freelancer ' . $freelancer->getNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') hinzugefügt', 'shift');
        }
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

    public function addShiftFreelancerMaster(Request $request, Shift $shift, Freelancer $freelancer): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Freelancer ' . $freelancer->getNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') als Meister hinzugefügt', 'shift');
        }
        $shift->freelancer()->attach($freelancer->id, ['is_master' => true]);
    }



    public function addShiftProviderMaster(Request $request, Shift $shift, ServiceProvider $serviceProvider): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') als Meister hinzugefügt', 'shift');
        }
        $shift->service_provider()->attach($serviceProvider->id, ['is_master' => true]);
    }

    public function removeUser(Shift $shift, User $user): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
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
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Freelancer ' . $freelancer->getNameAttribute() . ' wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
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
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
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
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') hinzugefügt', 'shift');
        }
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

    public function clearEmployeesAndMaster(Shift $shift): void
    {
        $users = $shift->users()->get();
        foreach ($users as $user) {
            $this->removeUser($shift, $user);
        }
        $freelancers = $shift->freelancer()->get();
        foreach($freelancers as $freelancer) {
            $this->removeFreelancer($shift, $freelancer);
        }
        $serviceProviders = $shift->service_provider()->get();
        foreach($serviceProviders as $serviceProvider) {
            $this->removeProvider($shift, $serviceProvider);
        }
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Alle eingeplanten Mitarbeiter wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
    }
}
