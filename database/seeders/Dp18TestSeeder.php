<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Test-Daten für DP-18 (Info-Modal + Überstunden).
 *
 * Erzeugt 2 dedizierte Test-User mit voll konfiguriertem Vertrag und Beispieldaten,
 * mit denen sich alle Tabs/Kennzahlen gegentesten lassen.
 *
 * Ausführen:  ddev exec php artisan db:seed --class=Dp18TestSeeder
 */
class Dp18TestSeeder extends Seeder
{
    private Carbon $today;
    private Carbon $seasonStart;
    private Carbon $seasonEnd;
    private int $balanceSum = 0;
    /** @var array<string, bool> reservierte (explizit gesetzte) Tage des aktuellen Users */
    private array $reserved = [];

    public function run(): void
    {
        $this->today = Carbon::today();
        // Spielzeit so legen, dass beide Hälften abgeschlossene (vergangene) Tage enthalten.
        $this->seasonStart = $this->today->copy()->subDays(200);
        $this->seasonEnd = $this->today->copy()->addDays(40);

        $this->setPlayingTime();
        $contract = $this->createContract();

        $userA = $this->ensureUser('dp18.test.a@artwork.test', 'DP18', 'Test-A');
        $userB = $this->ensureUser('dp18.test.b@artwork.test', 'DP18', 'Test-B');

        $craft = Craft::query()->orderBy('id')->first();
        foreach ([$userA, $userB] as $user) {
            $this->assignContract($user, $contract);
            $this->workPattern($user);
            // Gewerk-Zuordnung, damit die User im Schichtplan erscheinen (Info-Icon sichtbar)
            if ($craft) {
                $user->crafts()->syncWithoutDetaching([$craft->id]);
            }
            $this->cleanup($user);
        }

        $this->seedUserA($userA);
        $this->seedUserB($userB);

        $this->command->info('--------------------------------------------------------');
        $this->command->info('DP-18 Testdaten angelegt.');
        $this->command->info('Spielzeit: ' . $this->seasonStart->toDateString() . ' – ' . $this->seasonEnd->toDateString());
        $this->command->info('User A (volle Daten): dp18.test.a@artwork.test  [#' . $userA->id . ']');
        $this->command->info('User B (negatives Konto / kein Overtime): dp18.test.b@artwork.test  [#' . $userB->id . ']');
        $this->command->info('Vertrag: "' . $contract->name . '" (alle Parameter aktiv, Überstunden-Frist 30 Tage)');
        $this->command->warn('Danach ausführen: ddev exec php artisan artwork:track-shift-kpis && ddev exec php artisan artwork:mark-payable-overtime');
        $this->command->info('Im Schichtplan das Info-Icon je User öffnen (Permission "can view shift user kpis" / "can pay out overtime").');
        $this->command->info('--------------------------------------------------------');
    }

    private function setPlayingTime(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->playing_time_window_start = $this->seasonStart->toDateString();
        $settings->playing_time_window_end = $this->seasonEnd->toDateString();
        $settings->save();
    }

    private function createContract(): UserContract
    {
        return UserContract::updateOrCreate(
            ['name' => 'DP-18 Test-Vertrag'],
            [
                'free_full_days_per_week' => 2,
                'free_half_days_per_week' => 1,
                'special_day_rule_active' => true,
                'compensation_period' => 90,
                'description' => 'Automatisch angelegter Testvertrag für DP-18.',
                'free_sundays_per_season' => 8,
                'free_sundays_per_season_active' => true,
                'days_off_first_26_weeks' => 10,
                'days_off_first_26_weeks_active' => true,
                'free_sundays_sat_mon_per_half' => 3,
                'free_sundays_sat_mon_per_half_active' => true,
                'free_sundays_and_saturdays_per_season' => 4,
                'free_sundays_and_saturdays_per_season_active' => true,
                'free_sundays_per_calendar_year' => 12,
                'free_sundays_per_calendar_year_active' => true,
                'one_and_half_day_combinations' => 5,
                'one_and_half_day_combinations_active' => true,
                'annual_vacation_days' => 30,
                'overtime_rule_active' => true,
                'overtime_compensation_period' => 30,
            ]
        );
    }

    private function ensureUser(string $email, string $first, string $last): User
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::factory()->create([
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
            ]);
        }
        $user->forceFill(['can_work_shifts' => true])->saveQuietly();

        return $user;
    }

    private function assignContract(User $user, UserContract $contract): void
    {
        UserContractAssign::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_contract_id' => $contract->id,
                'free_sundays_per_season' => $contract->free_sundays_per_season,
                'days_off_first_26_weeks' => $contract->days_off_first_26_weeks,
                'overtime_rule_active' => $contract->overtime_rule_active,
                'overtime_compensation_period' => $contract->overtime_compensation_period,
            ]
        );
    }

    private function workPattern(User $user): void
    {
        UserWorkTime::updateOrCreate(
            ['user_id' => $user->id, 'is_active' => true],
            [
                'monday' => '08:00:00',
                'tuesday' => '08:00:00',
                'wednesday' => '08:00:00',
                'thursday' => '08:00:00',
                'friday' => '08:00:00',
                'saturday' => null,
                'sunday' => null,
                'valid_from' => $this->today->copy()->subDays(300)->toDateString(),
                'valid_until' => null,
            ]
        );
    }

    private function cleanup(User $user): void
    {
        Vacation::where('vacationer_type', User::class)->where('vacationer_id', $user->id)
            ->where('comment', 'like', 'DP18%')->delete();
        WorkTimeBooking::where('user_id', $user->id)->where('name', 'like', 'dp18_%')->delete();
        CompensationDayOff::where('user_id', $user->id)->where('reason', 'like', 'DP18%')->delete();
        OvertimePayout::where('user_id', $user->id)->where('comment', 'like', 'DP18%')->delete();
        IndividualTime::where('timeable_type', User::class)->where('timeable_id', $user->id)
            ->where('title', 'like', 'DP18%')->delete();
        $this->balanceSum = 0;
        $this->reserved = [];
    }

    /** Belegt alle geplanten Arbeitstage (Mo–Fr) der Spielzeit außer den explizit gesetzten Tagen. */
    private function occupyWorkingDays(User $user): void
    {
        $yesterday = $this->today->copy()->subDay();
        for ($d = $this->seasonStart->copy(); $d->lte($yesterday); $d->addDay()) {
            if ($d->isWeekend()) {
                continue;
            }
            if (isset($this->reserved[$d->toDateString()])) {
                continue;
            }
            IndividualTime::create([
                'timeable_type' => User::class,
                'timeable_id' => $user->id,
                'title' => 'DP18-Arbeitstag',
                'start_date' => $d->toDateString(),
                'end_date' => $d->toDateString(),
                'full_day' => true,
                'working_time_minutes' => 480,
                'break_minutes' => 0,
            ]);
        }
    }

    /** Sonntag vor "heute", $weeks Wochen zurück. */
    private function sunday(int $weeks): Carbon
    {
        return $this->today->copy()->previous(Carbon::SUNDAY)->subWeeks($weeks);
    }

    private function freeDay(User $user, Carbon $date, string $dayPart = 'full'): void
    {
        $this->reserved[$date->toDateString()] = true;
        Vacation::create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => $date->toDateString(),
            'full_day' => $dayPart === 'full',
            'day_part' => $dayPart,
            'type' => 'FREE_WORK',
            'comment' => 'DP18-FREE',
            'is_series' => false,
            'created_by' => $user->id,
        ]);
    }

    private function offWork(User $user, Carbon $date): void
    {
        $this->reserved[$date->toDateString()] = true;
        Vacation::create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => $date->toDateString(),
            'full_day' => true,
            'day_part' => 'full',
            'type' => 'OFF_WORK',
            'comment' => 'DP18-URLAUB',
            'is_series' => false,
            'created_by' => $user->id,
        ]);
    }

    private function comp(User $user, float $value, ?Carbon $grantedDate, Carbon $deadline, bool $forHoliday, string $reason): void
    {
        if ($grantedDate) {
            $this->reserved[$grantedDate->toDateString()] = true;
        }
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => $value,
            'deadline' => $deadline->toDateString(),
            'granted_date' => $grantedDate?->toDateString(),
            'granted_at' => $grantedDate ? now() : null,
            'granted_by' => $grantedDate ? $user->id : null,
            'reason' => $reason,
            'for_holiday' => $forHoliday,
        ]);
    }

    private function booking(User $user, Carbon $date, int $wantedMinutes, int $workedMinutes): void
    {
        $change = $workedMinutes - $wantedMinutes;
        $this->reserved[$date->toDateString()] = true;
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'booker_id' => $user->id,
            'name' => 'dp18_booking_' . $date->toDateString(),
            'booking_day' => $date->toDateString(),
            'booking_weekday' => $date->dayOfWeek,
            'wanted_working_hours' => $wantedMinutes,
            'worked_hours' => $workedMinutes,
            'nightly_working_hours' => 0,
            'is_special_day' => false,
            'work_time_balance_change' => $change,
        ]);
        $this->balanceSum += $change;
    }

    private function seedUserA(User $user): void
    {
        $this->reserved = [];
        $this->balanceSum = 0;

        // --- Freie Sonntage (FREE_WORK ganztägig) + angrenzende Sa/Mo ---
        // 1. Hälfte (älter als ~11 Wochen)
        $s14 = $this->sunday(14);
        $this->freeDay($user, $s14);
        $this->freeDay($user, $s14->copy()->subDay()); // Samstag frei -> Sa/Mo + Sun+Sat
        $s12 = $this->sunday(12);
        $this->freeDay($user, $s12);
        $this->freeDay($user, $s12->copy()->addDay()); // Montag frei -> Sa/Mo
        $this->freeDay($user, $this->sunday(16)); // Sonntag allein -> nur Saison/Jahr
        // 2. Hälfte (letzte ~Wochen, vergangen)
        $s4 = $this->sunday(4);
        $this->freeDay($user, $s4);
        $this->freeDay($user, $s4->copy()->subDay()); // Samstag frei
        $s2 = $this->sunday(2);
        $this->freeDay($user, $s2);
        $this->freeDay($user, $s2->copy()->addDay()); // Montag frei

        // --- 1,5-Tage-Kombinationen ---
        // 1. Hälfte: 2 aufeinanderfolgende GFT (Di+Mi ~13 Wochen)
        $a = $this->today->copy()->subWeeks(13)->next(Carbon::TUESDAY);
        $this->freeDay($user, $a);
        $this->freeDay($user, $a->copy()->addDay());
        // 1. Hälfte: 3 aufeinanderfolgende GFT (~15 Wochen) -> 2 Kombis
        $b = $this->today->copy()->subWeeks(15)->next(Carbon::MONDAY);
        $this->freeDay($user, $b);
        $this->freeDay($user, $b->copy()->addDay());
        $this->freeDay($user, $b->copy()->addDays(2));
        // 2. Hälfte: einzelner GFT + halber freier Nachmittag am Vortag
        $c = $this->today->copy()->subWeeks(5)->next(Carbon::WEDNESDAY);
        $this->freeDay($user, $c->copy()->subDay(), 'afternoon'); // halber freier Nachmittag davor
        $this->freeDay($user, $c); // GFT

        // --- weitere gewährte halbe freie Tage ---
        $this->freeDay($user, $this->today->copy()->subWeeks(3)->next(Carbon::THURSDAY), 'morning');

        // --- Urlaub (OFF_WORK) im aktuellen Kalenderjahr: 5 Tage ---
        $u = $this->today->copy()->subWeeks(8)->next(Carbon::MONDAY);
        for ($i = 0; $i < 5; $i++) {
            $this->offWork($user, $u->copy()->addDays($i));
        }

        // --- Ersatzfreie Tage (CompensationDayOff) ---
        // gewährt, Sondertag (zählt NICHT als Überstundenabbau)
        $this->comp($user, 1.0, $this->today->copy()->subWeeks(6), $this->today->copy()->addWeeks(6), true, 'DP18 gewährt (Sondertag)');
        // offen (noch nicht gewährt) -> erscheint in "offene ersatzfreie Tage"
        $this->comp($user, 0.5, null, $this->today->copy()->addWeeks(3), false, 'DP18 offen');

        // --- Überstunden (work_time_bookings) ---
        // alter Anfall: Frist (Anfall + 30 Tage) bereits überschritten -> auszuzahlend
        $this->booking($user, $this->today->copy()->subDays(70), 480, 1080); // +600 (10h)
        // Minus-Tag (Abbau, FIFO -> verbraucht ältesten Chunk)
        $this->booking($user, $this->today->copy()->subDays(50), 480, 360);  // -120 (2h)
        // jüngerer Anfall: Frist noch offen
        $this->booking($user, $this->today->copy()->subDays(10), 480, 660);  // +180 (3h)
        // eine normale Woche für die Ist-Stunden-Ansicht
        $weekMon = $this->today->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
        $this->booking($user, $weekMon->copy(), 480, 480);            // 0
        $this->booking($user, $weekMon->copy()->addDay(), 480, 540);  // +60
        $this->booking($user, $weekMon->copy()->addDays(2), 480, 420); // -60
        $this->booking($user, $weekMon->copy()->addDays(3), 480, 480); // 0
        $this->booking($user, $weekMon->copy()->addDays(4), 480, 480); // 0

        $user->forceFill(['work_time_balance' => $this->balanceSum])->saveQuietly();

        // Beispiel-Auszahlung in der Historie
        OvertimePayout::create([
            'user_id' => $user->id,
            'minutes' => 120,
            'payout_date' => $this->today->copy()->subDays(5)->toDateString(),
            'created_by' => $user->id,
            'comment' => 'DP18 Beispiel-Auszahlung',
        ]);
        // die Auszahlung reduziert das Konto ebenfalls
        $user->forceFill(['work_time_balance' => $this->balanceSum - 120])->saveQuietly();

        $this->occupyWorkingDays($user);
    }

    private function seedUserB(User $user): void
    {
        $this->reserved = [];
        $this->balanceSum = 0;

        // Negatives Zeitkonto -> keine ausgewiesenen Überstunden, kein auszuzahlend.
        $this->booking($user, $this->today->copy()->subDays(40), 480, 180); // -300
        $this->booking($user, $this->today->copy()->subDays(33), 480, 360); // -120
        $this->booking($user, $this->today->copy()->subDays(20), 480, 680); // +200 (bleibt im Minus)
        $user->forceFill(['work_time_balance' => $this->balanceSum])->saveQuietly();

        // ein paar freie Sonntage, damit auch hier Spielzeitdaten sichtbar sind
        $this->freeDay($user, $this->sunday(3));
        $this->freeDay($user, $this->sunday(6));

        $this->occupyWorkingDays($user);
    }
}
