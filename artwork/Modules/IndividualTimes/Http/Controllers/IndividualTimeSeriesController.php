<?php

namespace Artwork\Modules\IndividualTimes\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Http\Requests\StoreIndividualTimeSeriesRequest;
use Artwork\Modules\IndividualTimes\Http\Requests\UpdateIndividualTimeSeriesRequest;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Artwork\Modules\IndividualTimes\Services\IndividualTimeSeriesService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndividualTimeSeriesController extends Controller
{
    public function __construct(
        protected readonly IndividualTimeSeriesService $individualTimeSeriesService,
        protected readonly AuthManager $auth
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
    public function store(StoreIndividualTimeSeriesRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();

        // subjects: [{type: "user"|"freelancer"|"service_provider", id: 123}, ...]
        $subjectsInput = collect($data['subjects']);

        // Zeit-Subjekte aus DB laden
        $timeables = $this->resolveTimeables($subjectsInput);

        // created_by (optional)
        $data['created_by'] = $this->auth->id();

        $series = $this->individualTimeSeriesService->createSeriesForTimeables($data, $timeables);


        // Beispiel: Redirect zurück mit Flash
        return back()->with('success', __('Individual time series created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(IndividualTimeSeries $series, Request $request)
    {
        $subjectType = $request->query('subject_type'); // 'user' | 'freelancer' | 'service_provider'
        $subjectId   = $request->query('subject_id');

        $timeQuery = IndividualTime::query()
            ->where('series_uuid', $series->uuid);

        if ($subjectType && $subjectId) {
            $timeableClass = $this->resolveTimeableClassFromSubjectType($subjectType);

            $timeQuery
                ->where('timeable_type', $timeableClass)
                ->where('timeable_id', $subjectId);
        }

        /** @var IndividualTime|null $sample */
        $sample = $timeQuery->orderBy('start_date')->first();

        return response()->json([
            'data' => [
                'uuid'                 => $series->uuid,
                'title'                => $series->title,
                'start_date'           => optional($series->start_date)?->toDateString(),
                'end_date'             => optional($series->end_date)?->toDateString(),
                'frequency'            => $series->frequency,
                'interval'             => $series->interval,
                'weekdays'             => $series->weekdays ?? [],
                'full_day'             => (bool) optional($sample)->full_day,
                'start_time'           => optional($sample)->start_time,
                'end_time'             => optional($sample)->end_time,
                'working_time_minutes' => optional($sample)->working_time_minutes,
                'break_minutes'        => optional($sample)->break_minutes ?? 0,
            ],
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IndividualTimeSeries $individualTimeSeries): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIndividualTimeSeriesRequest $request, IndividualTimeSeries $series)
    {
        $data = $this->validatePayload($request);

        // Wer bearbeitet?
        $createdBy = $this->auth->id();

        // Neue Serie anlegen (Kopie mit neuen Werten)
        $newSeries = IndividualTimeSeries::create([
            // uuid: falls im Model auto-generiert, weglassen – sonst:
            'uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'title'      => $data['title'] ?? $series->title,
            'start_date' => Carbon::parse($data['start_date'])->startOfDay(),
            'end_date'   => Carbon::parse($data['end_date'])->endOfDay(),
            'frequency'  => $data['frequency'],
            'interval'   => $data['interval'],
            'weekdays'   => $data['weekdays'],
            'created_by' => $createdBy,
        ]);

        // Für die übergebenen Subjects:
        // - alte Einträge (alte Serie) löschen
        // - neue Einträge mit neuer Series-UUID erzeugen
        $this->regenerateIndividualTimesForSubjects(
            sourceSeries: $series,     // alte
            targetSeries: $newSeries,  // neue
            data: $data
        );

    }

    /**
     * Entfernt die Serie und alle zugehörigen IndividualTimes.
     */
    public function destroy(IndividualTimeSeries $series): void
    {
        // Alle IndividualTimes der Serie löschen
        $series->individualTimes()->delete();
        // Serie selbst löschen
        $series->delete();
    }

    protected function resolveTimeables(Collection $subjects): Collection
    {
        $users           = $subjects->where('type', 'user')->pluck('id')->all();
        $freelancers     = $subjects->where('type', 'freelancer')->pluck('id')->all();
        $serviceProvider = $subjects->where('type', 'service_provider')->pluck('id')->all();

        $collection = collect();

        if (! empty($users)) {
            $collection = $collection->merge(
                User::query()->whereIn('id', $users)->get()
            );
        }

        if (! empty($freelancers)) {
            $collection = $collection->merge(
                Freelancer::query()->whereIn('id', $freelancers)->get()
            );
        }

        if (! empty($serviceProvider)) {
            $collection = $collection->merge(
                ServiceProvider::query()->whereIn('id', $serviceProvider)->get()
            );
        }

        return $collection;
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'title'                 => ['nullable', 'string', 'max:255'],
            'start_date'            => ['required', 'date'],
            'end_date'              => ['required', 'date', 'after_or_equal:start_date'],
            'full_day'              => ['sometimes', 'boolean'],
            'start_time'            => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request): void {
                    if (!$request->boolean('full_day') && empty($value)) {
                        $fail(__('Start time is required if not full day.'));
                    }
                },
            ],
            'end_time'              => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request): void {
                    if (!$request->boolean('full_day') && empty($value)) {
                        $fail(__('End time is required if not full day.'));
                    }
                },
            ],
            'working_time_minutes'  => ['nullable', 'integer', 'min:0'],
            'break_minutes'         => ['nullable', 'integer', 'min:0'],
            'frequency'             => ['required', Rule::in(['weekly'])],
            'interval'              => ['required', 'integer', 'min:1'],
            'weekdays'              => ['required', 'array', 'min:1'],
            'weekdays.*'            => ['integer', 'between:1,7'],
            'subjects'              => ['required', 'array', 'min:1'],
            'subjects.*.id'         => ['required', 'integer'],
            'subjects.*.type'       => ['required', Rule::in(['user', 'freelancer', 'service_provider'])],
            // optional für store: series_uuid (wenn du z. B. im Frontend schon eine UUID erzeugst)
            'series_uuid'           => ['nullable', 'uuid'],
        ]);
    }

    /**
     * Erzeugt alle Occurrences und ersetzt alle IndividualTimes für die
     * übergebenen Subjects und die gegebene Series.
     *
     * Wichtig: Nur diese Subjects werden gelöscht/neu angelegt – andere
     * Personen mit derselben series_uuid bleiben unangetastet.
     */
    protected function regenerateIndividualTimesForSubjects(
        IndividualTimeSeries $sourceSeries,
        IndividualTimeSeries $targetSeries,
        array $data
    ): void {
        $start   = Carbon::parse($data['start_date'])->startOfDay();
        $end     = Carbon::parse($data['end_date'])->endOfDay();
        $fullDay = (bool) ($data['full_day'] ?? false);

        $occurrences = $this->generateOccurrences(
            $start,
            $end,
            $data['weekdays'],
            $data['frequency'],
            (int) $data['interval']
        );

        foreach ($data['subjects'] as $subject) {
            $subjectId   = $subject['id'];
            $subjectType = $subject['type'];

            $timeableClass = $this->resolveTimeableClassFromSubjectType($subjectType);

            // 1) Alte Zeiten NUR für dieses Subject + alte Serie löschen
            IndividualTime::query()
                ->where('series_uuid', $sourceSeries->uuid)
                ->where('timeable_type', $timeableClass)
                ->where('timeable_id', $subjectId)
                ->delete();

            // 2) Neue Zeiten für dieses Subject + neue Serie erzeugen
            foreach ($occurrences as $date) {
                // Arbeitszeit wie bei Einzelzeit berechnen
                $startTime = $fullDay ? null : Arr::get($data, 'start_time');
                $endTime = $fullDay ? null : Arr::get($data, 'end_time');
                $breakMinutes = Arr::get($data, 'break_minutes', 0);
                if ($startTime && $endTime) {
                    $startDateForConvert = Carbon::parse($date->toDateString() . ' ' . $startTime);
                    $startTimeConverted = Carbon::parse($date->toDateString() . ' ' . $startTime);
                    $endTimeConverted = Carbon::parse($date->toDateString() . ' ' . $endTime);
                    $totalMinutes = $startTimeConverted->diffInMinutes($endTimeConverted);
                    $workingTimeInMinutes = max(0, $totalMinutes - $breakMinutes);
                } else {
                    $workingTimeInMinutes = 1440;
                }
                IndividualTime::create([
                    'series_uuid'             => $targetSeries->uuid,
                    'timeable_type'           => $timeableClass,
                    'timeable_id'             => $subjectId,
                    'title'                   => $data['title'] ?? null,
                    'start_date'              => $date->toDateString(),
                    'end_date'                => $date->toDateString(),
                    'start_time'              => $startTime,
                    'end_time'                => $endTime,
                    'full_day'                => $fullDay,
                    'working_time_minutes'    => $workingTimeInMinutes,
                    'break_minutes'           => $breakMinutes,
                    'days_of_individual_time' => [$date->toDateString()],
                ]);
            }
        }
    }



    /**
     * Erzeugt alle Occurrence-Daten (Carbon) innerhalb [start, end],
     * die zu den gewünschten Wochentagen + Intervall passen.
     *
     * Aktuell nur 'weekly' unterstützt.
     */
    protected function generateOccurrences(
        Carbon $start,
        Carbon $end,
        array $weekdays,
        string $frequency,
        int $interval
    ): array {
        $results = [];

        // Sicherstellen, dass Weekdays unique & sortiert sind
        $weekdays = array_values(array_unique($weekdays));
        sort($weekdays);

        // Use the ISO week start (Monday) of the start date as reference
        // so that all days within the same week share the same week number.
        $weekReference = $start->copy()->startOfWeek(Carbon::MONDAY);
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $weekday = (int) $cursor->dayOfWeekIso; // 1 = Mo, 7 = So

            if (in_array($weekday, $weekdays, true)) {
                if ($frequency === 'weekly') {
                    $cursorWeekStart = $cursor->copy()->startOfWeek(Carbon::MONDAY);
                    $weeksDiff = (int) round($weekReference->floatDiffInWeeks($cursorWeekStart));
                    if ($weeksDiff % $interval === 0) {
                        $results[] = $cursor->copy();
                    }
                }
            }

            $cursor->addDay();
        }

        return $results;
    }

    protected function resolveTimeableClassFromSubjectType(string $subjectType): string
    {
        return match ($subjectType) {
            'user'            => User::class,
            'freelancer'      => Freelancer::class,
            'service_provider'=> ServiceProvider::class,
            default           => User::class, // Fallback, sollte eigentlich nie passieren
        };
    }

}
