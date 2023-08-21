<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\Shift;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use function React\Promise\reject;

class ShiftController extends Controller
{

    protected ?NewHistoryService $history = null;

    /**
     * ShiftController constructor.
     */
    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\Shift');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
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

        $shift->update([
            'event_start_day' => Carbon::parse($event->start_time)->format('Y-m-d'),
            'event_end_day' => Carbon::parse($event->end_time)->format('Y-m-d'),
        ]);

        $shiftUuid = Str::uuid();

        if($request->changeAll){

            $start = Carbon::parse($request->changes_start)->startOfDay();
            $end = Carbon::parse($request->changes_end)->endOfDay();
            $seriesEvents = Event::where('series_id', $event->series_id)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end]);
                })
                ->get();


            foreach ($seriesEvents as $seriesEvent){
                if($seriesEvent->id != $event->id){
                    $newShift = $seriesEvent->shifts()->create($request->only([
                        'start',
                        'end',
                        'break_minutes',
                        'craft_id',
                        'number_employees',
                        'number_masters',
                        'description',
                    ]));
                    $newShift->update([
                        'shift_uuid' => $shiftUuid,
                        'event_start_day' => Carbon::parse($seriesEvent->start_time)->format('Y-m-d'),
                        'event_end_day' => Carbon::parse($seriesEvent->end_time)->format('Y-m-d'),
                    ]);
                }
            }
        }

        if($event->is_series){
            $shift->update([
                'shift_uuid' => $shiftUuid,
            ]);
        }

        $this->history->createHistory($shift->id, 'Schicht von Event '. $event->eventName .' wurde erstellt', 'shift');
    }

    /**
     * Display the specified resource.
     *
     * @param Shift $shift
     * @return Response
     */
    public function show(Shift $shift)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Shift $shift
     * @return Response
     */
    public function edit(Shift $shift)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Shift $shift
     * @return RedirectResponse
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

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Shift $shift
     * @return RedirectResponse
     */
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

        return Redirect::route('projects.show.shift')->with('success', 'Shift updated');
    }

    public function updateCommitments(Request $request)
    {
        $projectId = $request->input('project_id');
        $shiftIds = $request->input('shifts');
        $updateData = $request->only([
            'is_committed',
        ]);

        Debugbar::info($shiftIds);

        Shift::whereIn('id', $shiftIds)->update($updateData);
        return Redirect::route('projects.show.shift', $projectId)->with('success', 'Shift updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Shift $shift
     * @return void
     */
    public function destroy(Shift $shift): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Schicht von Event '. $event->eventName .' wurde gelöscht', 'shift');
        }
        $shift->delete();
    }


    /**
     * @param Shift $shift
     * @param array $eventIds
     * @param int|null $user_id
     * @param int|null $freelancer_id
     * @param int|null $service_provider_id
     * @return Collection
     */
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

    /**
     * @param Shift $shift
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function addShiftUser(Shift $shift, User $user, Request $request): void
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

        //add user to the project team of the project of the event of the shift, if the user is not already in the project team
        $event = $shift->event;
        $project = $event->project;
        if(!$project->users->contains($user->id)){
            $project->users()->attach($user->id);
        }

        if($request->chooseData['onlyThisDay'] === false) {
            $start = Carbon::parse($request->chooseData['start'])->startOfDay();
            $end = Carbon::parse($request->chooseData['end'])->endOfDay();
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('event_start_day', [$start, $end])
                        ->orWhereBetween('event_end_day', [$start, $end]);
                })
                ->get();
            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    if($request->chooseData['dayOfWeek'] !== 'all'){
                        if(Carbon::parse($allShift->event_start_day)->dayOfWeek !== $request->chooseData['dayOfWeek']){
                            continue;
                        }
                    }
                    if($allShift->getEmptyUserCountAttribute() > 0){
                        if(!$allShift->users->contains($user->id)) {
                            $allShift->users()->attach($user->id, ['shift_count' => $collideCount + 1]);
                        }
                    }
                }
            }
        }

        $shift->users()->attach($user->id, ['shift_count' => $collideCount + 1]);
    }

    /**
     * @param Request $request
     * @param Shift $shift
     * @param User $user
     * @return void
     */
    public function addShiftMaster(Request $request, Shift $shift, User $user): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde  zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') als Meister hinzugefügt', 'shift');
        }

        //add user to the project team of the project of the event of the shift, if the user is not already in the project team
        $event = $shift->event;
        $project = $event->project;
        if(!$project->users->contains($user->id)){
            $project->users()->attach($user->id);
        }

        if($request->chooseData['onlyThisDay'] === false) {
            $start = Carbon::parse($request->chooseData['start'])->startOfDay();
            $end = Carbon::parse($request->chooseData['end'])->endOfDay();
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('event_start_day', [$start, $end])
                        ->orWhereBetween('event_end_day', [$start, $end]);
                })
                ->get();
            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    if($request->chooseData['dayOfWeek'] !== 'all'){
                        if(Carbon::parse($allShift->event_start_day)->dayOfWeek !== $request->chooseData['dayOfWeek']){
                            continue;
                        }
                    }
                    if($allShift->getEmptyMasterCountAttribute() > 0){
                        if(!$allShift->users->contains($user->id)) {
                            $allShift->users()->attach($user->id, ['is_master' => true]);
                        }
                    }
                }
            }
        }

        $shift->users()->attach($user->id, ['is_master' => true]);
    }


    /**
     * add freelancer to shift
     * @param Shift $shift
     * @param Freelancer $freelancer
     * @param Request $request
     * @return void
     */
    public function addShiftFreelancer(Shift $shift, Freelancer $freelancer, Request $request): void
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

        if ($request->chooseData['onlyThisDay'] === false) {
            $start = Carbon::parse($request->chooseData['start'])->startOfDay();
            $end = Carbon::parse($request->chooseData['end'])->endOfDay();
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('event_start_day', [$start, $end])
                        ->orWhereBetween('event_end_day', [$start, $end]);
                })
                ->get();

            foreach ($allShifts as $allShift) {
                if($request->chooseData['dayOfWeek'] !== 'all'){
                    if(Carbon::parse($allShift->event_start_day)->dayOfWeek !== $request->chooseData['dayOfWeek']){
                        continue;
                    }
                }
                if ($allShift->id !== $shift->id) {
                    if($allShift->getEmptyUserCountAttribute() > 0){
                        if(!$allShift->freelancer->contains($freelancer->id)) {
                            $allShift->freelancer()->attach($freelancer->id, ['shift_count' => $collideCount + 1]);
                        }
                    }
                }
            }
        }

        $shift->freelancer()->attach($freelancer->id, ['shift_count' => $collideCount + 1]);
    }

    /**
     * @param Request $request
     * @param Shift $shift
     * @param Freelancer $freelancer
     * @return void
     */
    public function addShiftFreelancerMaster(Request $request, Shift $shift, Freelancer $freelancer): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Freelancer ' . $freelancer->getNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') als Meister hinzugefügt', 'shift');
        }

        if($request->chooseData['onlyThisDay'] === false) {
            $start = Carbon::parse($request->chooseData['start'])->startOfDay();
            $end = Carbon::parse($request->chooseData['end'])->endOfDay();
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('event_start_day', [$start, $end])
                        ->orWhereBetween('event_end_day', [$start, $end]);
                })
                ->get();
            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    if($request->chooseData['dayOfWeek'] !== 'all'){
                        if(Carbon::parse($allShift->event_start_day)->dayOfWeek !== $request->chooseData['dayOfWeek']){
                            continue;
                        }
                    }
                    if($allShift->getEmptyMasterCountAttribute() > 0) {
                        if(!$allShift->freelancer->contains($freelancer->id)) {
                            $allShift->freelancer()->attach($freelancer->id, ['is_master' => true]);
                        }
                    }
                }
            }
        }
        $shift->freelancer()->attach($freelancer->id, ['is_master' => true]);
    }


    /**
     * @param Request $request
     * @param Shift $shift
     * @param ServiceProvider $serviceProvider
     * @return void
     */
    public function addShiftProviderMaster(Request $request, Shift $shift, ServiceProvider $serviceProvider): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde zur Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') als Meister hinzugefügt', 'shift');
        }

        if($request->chooseData['onlyThisDay'] === false) {
            $start = Carbon::parse($request->chooseData['start'])->startOfDay();
            $end = Carbon::parse($request->chooseData['end'])->endOfDay();
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('event_start_day', [$start, $end])
                        ->orWhereBetween('event_end_day', [$start, $end]);
                })
                ->get();
            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    if($request->chooseData['dayOfWeek'] !== 'all'){
                        if(Carbon::parse($allShift->event_start_day)->dayOfWeek !== $request->chooseData['dayOfWeek']){
                            continue;
                        }
                    }
                    if($allShift->getEmptyMasterCountAttribute() > 0){
                        if(!$allShift->service_provider->contains($serviceProvider->id)) {
                            $allShift->service_provider()->attach($serviceProvider->id, ['is_master' => true]);
                        }
                    }
                }
            }
        }
        $shift->service_provider()->attach($serviceProvider->id, ['is_master' => true]);
    }

    /**
     * @param Shift $shift
     * @param User $user
     * @return void
     */
    public function removeUser(Shift $shift, User $user, Request $request): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
        $shift->users()->detach($user->id);

        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, user_id: $user->id);
        $collideCount = $collidingShiftIds->count();


        if($request->chooseData !== null){
            if ($request->chooseData['onlyThisDay'] === false) {
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->get();


                foreach ($allShifts as $allShift) {
                    if($allShift->id !== $shift->id){
                        $allShift->users()->detach($user->id);
                    }
                }
            }
        }

        $user->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );
    }

    /**
     * @param Shift $shift
     * @param Freelancer $freelancer
     * @return void
     */
    public function removeFreelancer(Shift $shift, Freelancer $freelancer, Request $request): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Freelancer ' . $freelancer->getNameAttribute() . ' wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
        $shift->freelancer()->detach($freelancer->id);

        if ($request->chooseData['onlyThisDay'] === false) {
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->get();


            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    $allShift->freelancer()->detach($freelancer->id);
                }
            }
        }

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

    /**
     * @param Shift $shift
     * @param ServiceProvider $serviceProvider
     * @return void
     */
    public function removeProvider(Shift $shift, ServiceProvider $serviceProvider, Request $request): void
    {
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
        $shift->service_provider()->detach($serviceProvider->id);


        if ($request->chooseData['onlyThisDay'] === false) {
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->get();


            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    $allShift->service_provider()->detach($serviceProvider->id);
                }
            }
        }

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

    /**
     * @param Shift $shift
     * @param ServiceProvider $serviceProvider
     * @param Request $request
     * @return void
     */
    public function addShiftProvider(Shift $shift, ServiceProvider $serviceProvider, Request $request): void
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

        if($request->chooseData['onlyThisDay'] === false) {
            $start = Carbon::parse($request->chooseData['start'])->startOfDay();
            $end = Carbon::parse($request->chooseData['end'])->endOfDay();
            $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('event_start_day', [$start, $end])
                        ->orWhereBetween('event_end_day', [$start, $end]);
                })
                ->get();
            foreach ($allShifts as $allShift) {
                if($allShift->id !== $shift->id){
                    if($request->chooseData['dayOfWeek'] !== 'all'){
                        if(Carbon::parse($allShift->event_start_day)->dayOfWeek !== $request->chooseData['dayOfWeek']){
                            continue;
                        }
                    }
                    if($allShift->getEmptyUserCountAttribute() > 0){
                        if(!$allShift->service_provider->contains($serviceProvider->id)) {
                            $allShift->service_provider()->attach($serviceProvider->id, ['is_master' => true]);
                        }
                    }
                }
            }
        }

        $shift->service_provider()->attach($serviceProvider->id, ['shift_count' => $collideCount + 1]);
    }

    /**
     * @param Shift $shift
     * @return void
     */
    public function clearEmployeesAndMaster(Shift $shift, Request $request): void
    {
        $users = $shift->users()->get();
        foreach ($users as $user) {
            $this->removeUser($shift, $user, $request);
        }
        $freelancers = $shift->freelancer()->get();
        foreach($freelancers as $freelancer) {
            $this->removeFreelancer($shift, $freelancer, $request);
        }
        $serviceProviders = $shift->service_provider()->get();
        foreach($serviceProviders as $serviceProvider) {
            $this->removeProvider($shift, $serviceProvider, $request);
        }
        if($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory($shift->id, 'Alle eingeplanten Mitarbeiter wurde von Schicht ('. $shift->craft()->first()->abbreviation .' - '. $event->eventName .') entfernt', 'shift');
        }
    }
}
