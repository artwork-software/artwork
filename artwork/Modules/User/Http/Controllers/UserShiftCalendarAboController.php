<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserShiftCalendarAboRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserShiftCalendarAboRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Services\UserShiftCalendarAboService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Properties\TextProperty;

class UserShiftCalendarAboController extends Controller
{
    public function __construct(
        private readonly UserShiftCalendarAboService $userShiftCalendarAboService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
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
    public function store(StoreUserShiftCalendarAboRequest $request): void
    {
        $this->userShiftCalendarAboService->create($request->validated(), Auth::id());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $calendar_abo_id)
    {
        // Retrieve Calendar Abo and related user
        $calendarAbo = UserShiftCalendarAbo::where('calendar_abo_id', $calendar_abo_id)->firstOrFail();
        $user = $calendarAbo->user;

        // Create Calendar
        $calendar = Calendar::create('Schichtplan ' . $user->full_name)
            ->refreshInterval(5)
            ->appendProperty(TextProperty::create('METHOD', 'PUBLISH'));
        // Ladefenster: nur Schichten von -30 Tagen bis +12 Monaten statt aller
        // Schichten der Person (Feeds werden von Kalender-Clients alle paar Minuten
        // abgerufen). Pivot-updated_at wird für LAST-MODIFIED mitgeladen.
        [$windowStart, $windowEnd] = $this->userShiftCalendarAboService->feedWindow();
        $user->load([
            'shifts' => static function ($query) use ($windowStart, $windowEnd): void {
                $query->withPivot('updated_at')
                    ->whereBetween('shifts.start_date', [$windowStart, $windowEnd]);
            },
            'shifts.event.creator',
            'shifts.event.project',
            'shifts.event.room',
            'shifts.room',
            'shifts.project',
            'shifts.craft',
            // Teilnehmer des ICS-Termins — kamen früher über das (entfernte) globale Shift-$with
            'shifts.users',
            'shifts.freelancer',
            'shifts.serviceProvider',
            'individualTimes',
        ]);
        $shifts = $this->userShiftCalendarAboService->getFilteredShifts($calendarAbo, $user->shifts);

        // Funktionsnamen einmal batchen (Beschreibung "Funktion: …"), kein Lookup pro Schicht
        $qualificationNames = ShiftQualification::query()
            ->whereIn('id', $shifts->pluck('pivot.shift_qualification_id')->filter()->unique())
            ->pluck('name', 'id');

        // Process each shift and add to the calendar
        foreach ($shifts as $shift) {
            if ($this->userShiftCalendarAboService->shouldAddShift($calendarAbo, $shift)) {
                $this->userShiftCalendarAboService->addShiftToCalendar(
                    $calendar,
                    $calendarAbo,
                    $shift,
                    $qualificationNames
                );
            }
        }

        // Individuelle Zeiten des Einsatzplans ebenfalls in den Feed aufnehmen
        // (kein Craft-Filter: sie sind personen-, nicht gewerkgebunden)
        $individualTimes = $this->userShiftCalendarAboService
            ->getFilteredIndividualTimes($calendarAbo, $user->individualTimes);
        foreach ($individualTimes as $individualTime) {
            $this->userShiftCalendarAboService->addIndividualTimeToCalendar(
                $calendar,
                $calendarAbo,
                $individualTime
            );
        }

        $dayServices = $user->dayServices()->withPivot('id')->orderByPivot('date');
        if ($calendarAbo->date_range) {
            $dayServices->wherePivotBetween('date', [$calendarAbo->start_date, $calendarAbo->end_date]);
        }

        foreach ($dayServices->get() as $dayService) {
            $this->userShiftCalendarAboService->addDayServiceToCalendar(
                $calendar,
                $calendarAbo,
                $dayService
            );
        }

        return response($calendar->get(), 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="schichtplan.ics"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserShiftCalendarAbo $userShiftCalendarAbo): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateUserShiftCalendarAboRequest $request,
        UserShiftCalendarAbo $userShiftCalendarAbo
    ): void {
        $this->userShiftCalendarAboService->updateByRequest($userShiftCalendarAbo, $request->validated());
    }

    /**
     * Widerruf des Feed-Links: erzeugt einen neuen Token, der alte Link liefert danach 404.
     * Nur die Besitzer*in darf ihr eigenes Abo erneuern (wie beim Update-Request).
     */
    public function destroy(UserShiftCalendarAbo $userShiftCalendarAbo): RedirectResponse
    {
        abort_unless(
            $userShiftCalendarAbo->user_id === Auth::id(),
            403,
            __('You can only renew your own calendar subscription link.')
        );

        $this->userShiftCalendarAboService->renewToken($userShiftCalendarAbo);

        return back()->with('success', __('Calendar link renewed. The old link is no longer valid.'));
    }
}
