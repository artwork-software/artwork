<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * KW-Festschreibungs-Anfragen: die letzte Kalenderwoche ist je Gewerk
 * angefragt UND bestätigt (Schichten festgeschrieben), die kommende Woche
 * hat offene Anfragen — die KW-Kacheln im Schichtplan zeigen damit beide
 * Zustände des Freigabeprozesses.
 */
class DemoShiftPlanRequestSeeder extends Seeder
{
    // Fenster-Parameter der Command-Pipeline (Anfragen hängen an "jetzt", nicht am Fenster)
    public ?string $from = null;
    public int $months = 6;

    public function run(): void
    {
        $context = new DemoContext();
        $planner = $context->plannerUser();
        $admin = $context->adminUser();

        $demoProjectIds = Project::query()
            ->get(['id', 'name'])
            ->filter(static fn (Project $project) => DemoProjectPools::archetypeForProjectName($project->name) !== null)
            ->pluck('id');

        $lastWeek = Carbon::now()->subWeek()->startOfWeek();
        $nextWeek = Carbon::now()->addWeek()->startOfWeek();

        $approved = $this->seedWeek($demoProjectIds, $lastWeek, 'approved', $planner->id, $admin->id);
        $pending = $this->seedWeek($demoProjectIds, $nextWeek, 'pending', $planner->id, null);

        $this->command?->info(sprintf(
            'KW-Anfragen: KW %d bestätigt (%d Gewerke), KW %d angefragt (%d Gewerke).',
            $lastWeek->isoWeek(),
            $approved,
            $nextWeek->isoWeek(),
            $pending
        ));
    }

    private function seedWeek(
        $demoProjectIds,
        Carbon $weekStart,
        string $status,
        int $requestedBy,
        ?int $reviewedBy
    ): int {
        $weekEnd = $weekStart->copy()->endOfWeek();

        $shiftsByCraft = Shift::query()
            ->whereIn('project_id', $demoProjectIds)
            ->whereBetween('start_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy('craft_id');

        $created = 0;
        foreach ($shiftsByCraft as $craftId => $craftShifts) {
            $request = ShiftPlanRequest::firstOrCreate(
                [
                    'craft_id' => $craftId,
                    'week_number' => $weekStart->isoWeek(),
                    'year' => $weekStart->isoWeekYear(),
                ],
                [
                    'status' => $status,
                    'requested_by_user_id' => $requestedBy,
                    'requested_at' => $weekStart->copy()->subDays(3)->setTime(10, 0),
                    'reviewed_by_user_id' => $reviewedBy,
                    'reviewed_at' => $reviewedBy !== null ? $weekStart->copy()->subDays(2)->setTime(9, 30) : null,
                    'review_comment' => $reviewedBy !== null ? 'Passt so, danke für die frühe Planung!' : null,
                ]
            );
            if (!$request->wasRecentlyCreated) {
                continue;
            }
            $created++;

            foreach ($craftShifts as $shift) {
                DB::table('shift_plan_request_shifts')->updateOrInsert(
                    ['shift_plan_request_id' => $request->id, 'shift_id' => $shift->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            Shift::query()->whereIn('id', $craftShifts->pluck('id'))->update([
                'current_request_id' => $request->id,
                // bestätigte Wochen sind festgeschrieben und raus aus dem Workflow,
                // offene Anfragen halten ihre Schichten im Workflow
                'in_workflow' => $status === 'pending',
                'is_committed' => $status === 'approved' ? true : DB::raw('is_committed'),
                'committing_user_id' => $status === 'approved' ? $reviewedBy : DB::raw('committing_user_id'),
            ]);
        }

        return $created;
    }
}
