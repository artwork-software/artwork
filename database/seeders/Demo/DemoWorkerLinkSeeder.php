<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Der Verknüpfungs-Workflow: verbindet ALLE Worker (auch die, die schon vor
 * dem Seeding existierten) mit Gewerken, Funktionen, Verträgen und
 * Arbeitszeitmustern. Verteilung: die meisten in einem Gewerk, einige in
 * mehreren; Azubis/VT-Allrounder sitzen in den universellen Gewerken.
 */
class DemoWorkerLinkSeeder extends Seeder
{
    private DemoContext $context;
    private DemoRandom $random;

    public function run(): void
    {
        $this->context = new DemoContext();
        $this->random = new DemoRandom('worker-links');

        $this->linkPoolUsers();
        $this->linkPoolFreelancers();
        $this->linkPoolServiceProviders();
        $this->linkRemainingWorkers();
        $this->ensureQualificationCoverage();
        $this->assignContractsToAllUsers();

        $this->command?->info('Verknüpfungs-Workflow abgeschlossen (Gewerke, Funktionen, Verträge, Arbeitszeiten).');
    }

    /** Nicht-universelle Gewerke aus dem Demo-Pool (für Zufalls-Extras). */
    private function regularCraftIds(): array
    {
        return $this->context->crafts()
            ->where('universally_applicable', false)
            ->whereIn('name', collect(DemoDataPools::CRAFTS)->pluck('name'))
            ->pluck('id')
            ->all();
    }

    private function linkPoolUsers(): void
    {
        foreach (DemoDataPools::USERS as $entry) {
            if ($entry['craft'] === null) {
                continue;
            }
            $user = $this->context->demoUser($entry['first'], $entry['last']);
            $craft = $this->context->craft($entry['craft']);
            if ($user === null || $craft === null) {
                continue;
            }

            $this->attachCraft($user, $craft);
            $qualification = $this->context->qualification($entry['qualification'] ?? 'mitarbeiter');
            $this->attachQualification($user, $qualification, $craft);

            // Schichtplaner*innen ihres Gewerks
            if (($entry['planner'] ?? false) === true) {
                $craft->craftShiftPlaner()->syncWithoutDetaching([$user->id]);
            }

            // Verteilung: ~25 % ein zweites, ~10 % ein drittes Gewerk (nicht für universelle Worker)
            if (!$craft->universally_applicable) {
                $rng = $this->random->fork('extra-crafts|' . $user->email);
                $extraCount = $rng->chance(0.10) ? 2 : ($rng->chance(0.25) ? 1 : 0);
                $extraIds = array_values(array_diff($this->regularCraftIds(), [$craft->id]));
                foreach ($rng->pickMany($extraIds, $extraCount) as $extraId) {
                    $extraCraft = $this->context->crafts()->firstWhere('id', $extraId);
                    $this->attachCraft($user, $extraCraft);
                    $this->attachQualification($user, $this->context->qualification('mitarbeiter'), $extraCraft);
                }
            }
        }

        $this->command?->info('Demo-User mit Gewerken/Funktionen verknüpft.');
    }

    private function linkPoolFreelancers(): void
    {
        foreach (DemoDataPools::FREELANCERS as $entry) {
            $freelancer = Freelancer::query()
                ->where('email', DemoDataPools::email($entry['first'], $entry['last']))
                ->first();
            $craft = $this->context->craft($entry['craft']);
            if ($freelancer === null || $craft === null) {
                continue;
            }
            $this->attachCraft($freelancer, $craft);
            $this->attachQualification($freelancer, $this->context->qualification($entry['qualification']), $craft);
        }
    }

    private function linkPoolServiceProviders(): void
    {
        foreach (DemoDataPools::SERVICE_PROVIDERS as $entry) {
            if ($entry['craft'] === null) {
                continue;
            }
            $provider = ServiceProvider::query()->where('provider_name', $entry['name'])->first();
            $craft = $this->context->craft($entry['craft']);
            if ($provider === null || $craft === null) {
                continue;
            }
            $this->attachCraft($provider, $craft);
            $this->attachQualification($provider, $this->context->qualification('mitarbeiter'), $craft);
        }
    }

    /**
     * Bestands-Worker (vor dem Seeding vorhanden) ohne Gewerk bekommen
     * dieselbe praxisnahe Verteilung: meist 1, manchmal 2 Gewerke.
     */
    private function linkRemainingWorkers(): void
    {
        $craftIds = $this->regularCraftIds();
        if ($craftIds === []) {
            return;
        }
        $mitarbeiter = $this->context->qualification('mitarbeiter');
        $linked = 0;

        $workers = collect()
            ->merge(User::query()->where('can_work_shifts', true)->get())
            ->merge(Freelancer::query()->where('can_work_shifts', true)->get())
            ->merge(ServiceProvider::query()->where('can_work_shifts', true)->get());

        foreach ($workers as $worker) {
            if ($worker->assignedCrafts()->exists()) {
                continue;
            }
            $rng = $this->random->fork('legacy|' . get_class($worker) . '|' . $worker->id);
            $count = $rng->chance(0.25) ? 2 : 1;
            foreach ($rng->pickMany($craftIds, $count) as $craftId) {
                $craft = $this->context->crafts()->firstWhere('id', $craftId);
                $this->attachCraft($worker, $craft);
                $this->attachQualification($worker, $mitarbeiter, $craft);
            }
            $linked++;
        }

        if ($linked > 0) {
            $this->command?->info(sprintf('%d Bestands-Worker ohne Gewerk nachverknüpft.', $linked));
        }
    }

    /**
     * Abdeckung sicherstellen: Da JEDE Funktion an JEDEM Gewerk hängt, braucht
     * jedes Gewerk je Funktion mindestens 2 zuordnenbare Personen — sonst gibt
     * es Schichtbedarfe, die niemand besetzen kann. Gewerke ohne Mitglieder
     * werden zuerst mit Demo-Workern aufgefüllt.
     */
    private function ensureQualificationCoverage(): void
    {
        $qualifications = ShiftQualification::all();
        $demoUserPool = $this->context->demoUsers()
            ->filter(static fn ($user) => $user->can_work_shifts)
            ->values();
        $added = 0;

        foreach ($this->context->crafts() as $craft) {
            $rng = $this->random->fork('coverage|' . $craft->id);

            $members = collect()
                ->merge($craft->users()->get())
                ->merge($craft->freelancers()->get())
                ->merge($craft->serviceProviders()->get());

            // Gewerke ohne (oder mit zu wenigen) Mitgliedern auffüllen
            if ($members->count() < 3 && $demoUserPool->isNotEmpty()) {
                $needed = 3 - $members->count();
                $candidates = $demoUserPool->reject(
                    fn ($user) => $members->contains(fn ($m) => $m instanceof User && $m->id === $user->id)
                )->values();
                foreach ($rng->pickMany($candidates->all(), $needed) as $user) {
                    $this->attachCraft($user, $craft);
                    $members->push($user);
                }
            }
            if ($members->isEmpty()) {
                continue;
            }

            foreach ($qualifications as $qualification) {
                $covered = DB::table('shift_qualifiables')
                    ->where('craft_id', $craft->id)
                    ->where('shift_qualification_id', $qualification->id)
                    ->count();
                $attempts = 0;
                while ($covered < 2 && $covered < $members->count() && $attempts++ < 20) {
                    // deterministisch Mitglieder ergänzen, die die Funktion noch nicht haben
                    $candidate = $rng->pick($members->all());
                    $exists = DB::table('shift_qualifiables')
                        ->where('craft_id', $craft->id)
                        ->where('shift_qualification_id', $qualification->id)
                        ->where('qualifiable_type', get_class($candidate))
                        ->where('qualifiable_id', $candidate->id)
                        ->exists();
                    if (!$exists) {
                        $this->attachQualification($candidate, $qualification, $craft);
                        $added++;
                    }
                    $covered = DB::table('shift_qualifiables')
                        ->where('craft_id', $craft->id)
                        ->where('shift_qualification_id', $qualification->id)
                        ->count();
                }
            }
        }

        if ($added > 0) {
            $this->command?->info(sprintf(
                'Funktions-Abdeckung: %d zusätzliche Gewerk-Funktions-Zuordnungen für lückenlose Besetzbarkeit.',
                $added
            ));
        }
    }

    /**
     * Verträge + Arbeitszeitmuster für alle User: Demo-User nach Pool,
     * Bestands-User bekommen einen passenden Default (nur wenn noch ohne Vertrag).
     */
    private function assignContractsToAllUsers(): void
    {
        $byEmail = [];
        foreach (DemoDataPools::USERS as $entry) {
            $byEmail[DemoDataPools::email($entry['first'], $entry['last'])] = $entry;
        }

        $assigned = 0;
        foreach (User::query()->get() as $user) {
            if (UserContractAssign::query()->where('user_id', $user->id)->exists()) {
                continue;
            }

            $entry = $byEmail[$user->email] ?? null;
            $contractKey = $entry['contract'] ?? ($user->can_work_shifts ? 'haustarif' : 'verwaltungs-default');
            $patternKey = $entry['pattern'] ?? ($user->can_work_shifts ? 'theaterbetrieb' : 'verwaltung');
            if ($contractKey === 'verwaltungs-default') {
                $contractKey = 'haustarif';
            }

            $contract = $this->context->contract($contractKey);
            $pattern = $this->context->workTimePattern($patternKey);
            if ($contract === null || $pattern === null) {
                continue;
            }

            $this->assignContract($user, $contract);
            $this->assignWorkTime($user, $pattern);

            if ($entry !== null) {
                $weekly = DemoDataPools::CONTRACTS[$entry['contract']]['weekly_hours'] ?? null;
                if ($weekly !== null && (float) $user->weekly_working_hours !== $weekly) {
                    $user->forceFill(['weekly_working_hours' => $weekly])->saveQuietly();
                }
            }
            $assigned++;
        }

        $this->command?->info(sprintf('Verträge/Arbeitszeitmuster für %d User zugewiesen.', $assigned));
    }

    private function assignContract(User $user, UserContract $contract): void
    {
        UserContractAssign::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_contract_id' => $contract->id,
                'free_full_days_per_week' => $contract->free_full_days_per_week,
                'free_half_days_per_week' => $contract->free_half_days_per_week,
                'special_day_rule_active' => $contract->special_day_rule_active,
                'compensation_period' => $contract->compensation_period,
                'overtime_rule_active' => $contract->overtime_rule_active,
                'overtime_compensation_period' => $contract->overtime_compensation_period,
                'free_sundays_per_season' => $contract->free_sundays_per_season,
                'days_off_first_26_weeks' => $contract->days_off_first_26_weeks,
            ]
        );
    }

    private function assignWorkTime(User $user, UserWorkTimePattern $pattern): void
    {
        UserWorkTime::updateOrCreate(
            ['user_id' => $user->id, 'is_active' => true],
            [
                'work_time_pattern_id' => $pattern->id,
                'monday' => $pattern->monday?->format('H:i:s'),
                'tuesday' => $pattern->tuesday?->format('H:i:s'),
                'wednesday' => $pattern->wednesday?->format('H:i:s'),
                'thursday' => $pattern->thursday?->format('H:i:s'),
                'friday' => $pattern->friday?->format('H:i:s'),
                'saturday' => $pattern->saturday?->format('H:i:s'),
                'sunday' => $pattern->sunday?->format('H:i:s'),
                'valid_from' => ($user->employStart
                    ? Carbon::parse($user->employStart)
                    : Carbon::now()->subYear())->toDateString(),
                'valid_until' => null,
            ]
        );
    }

    private function attachCraft(Model $worker, ?Craft $craft): void
    {
        if ($craft === null) {
            return;
        }
        $worker->assignedCrafts()->syncWithoutDetaching([$craft->id]);
    }

    private function attachQualification(Model $worker, ?ShiftQualification $qualification, ?Craft $craft): void
    {
        if ($qualification === null || $craft === null) {
            return;
        }
        DB::table('shift_qualifiables')->updateOrInsert(
            [
                'qualifiable_type' => get_class($worker),
                'qualifiable_id' => $worker->id,
                'shift_qualification_id' => $qualification->id,
                'craft_id' => $craft->id,
            ],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }
}
