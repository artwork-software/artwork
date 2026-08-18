<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Projektzuweisungen im Schichtplan: verbindliche Tageszuweisungen ("binding")
 * und Wünsche ("wish") von Personen zu Demo-Projekten — sichtbar als
 * Projekt-Chips in der Worker-Zeile des Schichtplans.
 */
class DemoProjectDayAssignmentSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    public function run(): void
    {
        $context = new DemoContext();
        $random = new DemoRandom('project-day-assignments');
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();

        $demoProjects = Project::query()
            ->get()
            ->filter(static fn (Project $project) => in_array(
                DemoProjectPools::archetypeForProjectName($project->name),
                ['eigenproduktion', 'gastspiel', 'konzert'],
                true
            ))
            ->values();
        if ($demoProjects->isEmpty()) {
            return;
        }

        $workers = $context->demoUsers()
            ->filter(static fn (User $user) => $user->can_work_shifts)
            ->values();

        $created = 0;
        foreach ($demoProjects as $project) {
            $firstEvent = $project->events()->min('start_time');
            $lastEvent = $project->events()->max('end_time');
            if ($firstEvent === null) {
                continue;
            }
            $periodStart = Carbon::parse($firstEvent)->max($windowStart);
            $periodEnd = Carbon::parse($lastEvent)->min($windowEnd);
            if ($periodStart->gt($periodEnd)) {
                continue;
            }

            $rng = $random->fork($project->name);
            // je Projekt 2-4 Personen mit Tageszuweisungen im Projektzeitraum
            foreach ($rng->pickMany($workers->all(), $rng->int(2, 4)) as $worker) {
                $type = $rng->chance(0.7) ? 'binding' : 'wish';
                $groupId = (string) Str::uuid();
                $dayCount = $rng->int(1, 3);
                $startOffset = $rng->int(0, max(0, (int) $periodStart->diffInDays($periodEnd) - $dayCount));

                for ($i = 0; $i < $dayCount; $i++) {
                    $date = $periodStart->copy()->addDays($startOffset + $i)->toDateString();

                    // Tage mit eigener Schicht auslassen (dort gilt die Schicht, nicht der Chip)
                    $hasShift = ShiftWorker::query()
                        ->where('employable_type', User::class)
                        ->where('employable_id', $worker->id)
                        ->whereHas('shift', fn ($query) => $query->whereDate('start_date', $date))
                        ->exists();
                    if ($hasShift) {
                        continue;
                    }

                    $assignment = ProjectDayAssignment::firstOrCreate(
                        [
                            'project_id' => $project->id,
                            'employable_type' => User::class,
                            'employable_id' => $worker->id,
                            'date' => $date,
                        ],
                        [
                            'type' => $type,
                            'group_id' => $groupId,
                            'is_full_period' => false,
                            'created_by' => $context->plannerUser()->id,
                        ]
                    );
                    if ($assignment->wasRecentlyCreated) {
                        $created++;
                    }
                }
            }
        }

        $this->command?->info(sprintf('Projektzuweisungen: %d Tageszuweisungen (binding/wish) angelegt.', $created));
    }
}
