<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;

/**
 * Urlaube für die Demo-Belegschaft und Arbeitszeit-Historie: aus den
 * festgeschriebenen (vergangenen) Schichten werden work_time_bookings je
 * User/Tag abgeleitet, daraus Zeitkonten-Stände und einzelne Überstunden
 * inkl. einer Beispiel-Auszahlung.
 */
class DemoAvailabilitySeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    private const BOOKING_PREFIX = 'demo_booking_';

    public function run(): void
    {
        $context = new DemoContext();
        $random = new DemoRandom('availability');
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();

        $this->seedVacations($context, $random, $windowStart);
        $this->seedAvailabilities($context, $random, $windowStart);
        $this->seedWorkTimeHistory($context, $random, $windowStart);
    }

    /**
     * Eingetragene Verfügbarkeiten: Freelancer und Teilzeitkräfte melden
     * regelmäßig, an welchen Tagen sie verfügbar sind — sichtbar im
     * Verfügbarkeitskalender und in der Worker-Zeile des Schichtplans.
     */
    private function seedAvailabilities(DemoContext $context, DemoRandom $random, Carbon $windowStart): void
    {
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();
        $reporters = collect()
            ->merge(Freelancer::query()->where('email', 'like', '%@' . DemoDataPools::EMAIL_DOMAIN)->get())
            ->merge(
                $context->demoUsers()->filter(
                    static fn (User $user) => $user->can_work_shifts && (float) $user->weekly_working_hours <= 20
                )
            );
        if ($reporters->isEmpty()) {
            return;
        }

        $created = 0;
        foreach ($reporters as $reporter) {
            $rng = $random->fork('availability|' . get_class($reporter) . '|' . $reporter->id);
            // je Person 2-3 feste Wochentage, an denen sie verfügbar ist
            $weekdays = $rng->pickMany([1, 2, 3, 4, 5, 6], $rng->int(2, 3));
            $fullDay = $rng->chance(0.5);

            for ($day = $windowStart->copy(); $day->lte($windowEnd); $day->addDay()) {
                if (!in_array($day->isoWeekday(), $weekdays, true) || $day->isPast()) {
                    continue;
                }
                $availability = Availability::firstOrCreate(
                    [
                        'available_type' => get_class($reporter),
                        'available_id' => $reporter->id,
                        'date' => $day->toDateString(),
                    ],
                    [
                        'full_day' => $fullDay,
                        'start_time' => $fullDay ? null : '10:00',
                        'end_time' => $fullDay ? null : '18:00',
                        'comment' => 'Demo: verfügbar',
                        'is_series' => false,
                    ]
                );
                if ($availability->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        $this->command?->info(sprintf('Verfügbarkeiten: %d Einträge angelegt.', $created));
    }

    private function seedVacations(DemoContext $context, DemoRandom $random, Carbon $windowStart): void
    {
        $workers = collect()
            ->merge($context->demoUsers()->filter(static fn (User $user) => $user->can_work_shifts))
            ->merge(Freelancer::query()->where('email', 'like', '%@' . DemoDataPools::EMAIL_DOMAIN)->get());
        if ($workers->isEmpty()) {
            return;
        }

        $created = 0;
        for ($i = 0; $i < $this->months; $i++) {
            $month = $windowStart->copy()->addMonths($i);
            $rng = $random->fork('vacation|' . $month->format('Y-m'));

            foreach ($rng->pickMany($workers->all(), $rng->int(3, 5)) as $worker) {
                $start = $month->copy()->startOfMonth()->addDays($rng->int(0, 20));
                $length = $rng->int(3, 10);

                for ($day = 0; $day < $length; $day++) {
                    $date = $start->copy()->addDays($day);
                    $vacation = Vacation::firstOrCreate(
                        [
                            'vacationer_type' => get_class($worker),
                            'vacationer_id' => $worker->id,
                            'date' => $date->toDateString(),
                            'type' => 'OFF_WORK',
                        ],
                        [
                            'full_day' => true,
                            'day_part' => 'full',
                            'comment' => 'Demo: Urlaub',
                            'is_series' => false,
                            'created_by' => $context->plannerUser()->id,
                        ]
                    );
                    if ($vacation->wasRecentlyCreated) {
                        $created++;
                    }
                }
            }
        }

        $this->command?->info(sprintf('Urlaube: %d Urlaubstage verteilt.', $created));
    }

    /**
     * Für jede festgeschriebene vergangene Demo-Schichtbesetzung entsteht eine
     * Tagesbuchung: Soll aus dem Arbeitszeitmuster, Ist aus der Schichtlänge.
     */
    private function seedWorkTimeHistory(DemoContext $context, DemoRandom $random, Carbon $windowStart): void
    {
        $assignments = ShiftWorker::query()
            ->where('employable_type', User::class)
            ->whereHas('shift', function ($query) use ($windowStart): void {
                $query->where('is_committed', true)
                    ->where('start_date', '>=', $windowStart->toDateString())
                    ->where('start_date', '<', Carbon::now()->toDateString());
            })
            ->with('shift')
            ->get()
            ->groupBy(static fn (ShiftWorker $row) => $row->employable_id . '|' . $row->shift->start_date->format('Y-m-d'));

        $balances = [];
        $bookingsCreated = 0;
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($assignments as $key => $rows) {
            [$userId, $day] = explode('|', $key);
            $user = $context->users()->firstWhere('id', (int) $userId);
            if ($user === null || !str_ends_with((string) $user->email, '@' . DemoDataPools::EMAIL_DOMAIN)) {
                continue; // Zeitkonten nur für Demo-User anfassen
            }

            $name = self::BOOKING_PREFIX . $day;
            if (WorkTimeBooking::query()->where('user_id', $userId)->where('name', $name)->exists()) {
                continue;
            }

            $workedMinutes = 0;
            $nightMinutes = 0;
            foreach ($rows as $row) {
                $shift = $row->shift;
                $start = Carbon::parse($shift->start_date->format('Y-m-d') . ' ' . $shift->start);
                $end = Carbon::parse($shift->end_date->format('Y-m-d') . ' ' . $shift->end);
                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }
                $minutes = max(0, $start->diffInMinutes($end) - (int) $shift->break_minutes);
                $workedMinutes += $minutes;
                if ((int) $end->format('H') <= 6 && $end->format('Y-m-d') !== $start->format('Y-m-d')) {
                    $nightMinutes += $end->copy()->startOfDay()->diffInMinutes($end);
                }
            }

            $date = Carbon::parse($day);
            $pattern = $user->workTimes?->firstWhere('is_active', true);
            $wantedTime = $pattern?->{$weekdays[$date->isoWeekday() - 1]} ?? null;
            $wantedMinutes = $wantedTime !== null
                ? (Carbon::parse($wantedTime)->hour * 60 + Carbon::parse($wantedTime)->minute)
                : (int) round(((float) $user->weekly_working_hours * 60) / 5);

            $change = $workedMinutes - $wantedMinutes;
            WorkTimeBooking::create([
                'user_id' => (int) $userId,
                'booker_id' => $context->plannerUser()->id,
                'name' => $name,
                'booking_day' => $date->toDateString(),
                'booking_weekday' => $date->dayOfWeek,
                'wanted_working_hours' => $wantedMinutes,
                'worked_hours' => $workedMinutes,
                'nightly_working_hours' => $nightMinutes,
                'is_special_day' => false,
                'work_time_balance_change' => $change,
            ]);
            $bookingsCreated++;
            $balances[(int) $userId] = ($balances[(int) $userId] ?? 0) + $change;
        }

        foreach ($balances as $userId => $balance) {
            User::query()->whereKey($userId)->update(['work_time_balance' => $balance]);
        }

        $this->seedOvertimes($context, $balances);
        $this->command?->info(sprintf(
            'Arbeitszeit-Historie: %d Tagesbuchungen für %d User erzeugt.',
            $bookingsCreated,
            count($balances)
        ));
    }

    /** @param array<int, int> $balances */
    private function seedOvertimes(DemoContext $context, array $balances): void
    {
        arsort($balances);
        $top = array_slice(array_keys(array_filter($balances, static fn (int $value) => $value > 120)), 0, 3, true);

        foreach ($top as $index => $userId) {
            $minutes = min(600, $balances[$userId]);
            UserOvertime::firstOrCreate(
                ['user_id' => $userId, 'date' => Carbon::now()->subDays(30 + $index * 7)->toDateString()],
                [
                    'minutes' => $minutes,
                    'remaining_minutes' => $index === 0 ? 0 : $minutes,
                    'deadline' => Carbon::now()->addDays(60)->toDateString(),
                    'status' => $index === 0 ? 'paid_out' : 'open',
                    'paid_out_minutes' => $index === 0 ? $minutes : 0,
                    'paid_out_by' => $index === 0 ? $context->adminUser()->id : null,
                    'paid_out_at' => $index === 0 ? Carbon::now()->subDays(5) : null,
                    'payout_reason' => $index === 0 ? 'Auszahlung mit der Novemberabrechnung (Demo).' : null,
                ]
            );

            if ($index === 0 && OvertimePayout::query()->where('user_id', $userId)->doesntExist()) {
                OvertimePayout::create([
                    'user_id' => $userId,
                    'minutes' => $minutes,
                    'payout_date' => Carbon::now()->subDays(5)->toDateString(),
                    'created_by' => $context->adminUser()->id,
                    'comment' => 'Beispiel-Auszahlung (Demo).',
                ]);
            }
        }

        if ($top !== []) {
            $this->command?->info(sprintf('Überstunden: %d Einträge (1 ausgezahlt).', count($top)));
        }
    }
}
