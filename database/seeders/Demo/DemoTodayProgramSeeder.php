<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;

/**
 * "Heute-Programm": stellt sicher, dass am Seed-Tag (und am Folgetag) etwas
 * im Haus passiert — das Dashboard ("Heutige Termine", "Deine Schichten")
 * wirkt sonst leer, wenn der Monatsplan den Tag zufällig ausspart. Läuft vor
 * dem DemoShiftSeeder, damit die Termine automatisch Schichten bekommen.
 */
class DemoTodayProgramSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    public function run(): void
    {
        $today = Carbon::today();
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();
        if ($today->lt($windowStart) || $today->gt($windowEnd)) {
            $this->command?->info('Heute liegt außerhalb des Seed-Fensters – Heute-Programm übersprungen.');

            return;
        }

        $context = new DemoContext();
        $rng = new DemoRandom('today|' . $today->toDateString());

        $project = $this->pickProject($today);
        if ($project === null) {
            $this->command?->warn('Kein Demo-Projekt für das Heute-Programm gefunden.');

            return;
        }

        $mainStage = $context->roomByName('Große Bühne');
        $foyer = $context->roomByName('Foyer');
        $rehearsal = $context->roomByName('Probebühne 1');

        $plan = [
            ['wartung', $mainStage, $today->copy()->setTime(8, 0), $today->copy()->setTime(10, 0), 'Wartung Obermaschinerie'],
            ['fuehrung', $foyer, $today->copy()->setTime(15, 0), $today->copy()->setTime(16, 0), 'Führung hinter die Kulissen'],
            ['vorstellung', $mainStage, $today->copy()->setTime(19, 30), $today->copy()->setTime(22, 0), 'Zusatzvorstellung'],
            ['probe', $rehearsal, $today->copy()->setTime(10, 0), $today->copy()->setTime(14, 0), 'Probe'],
            ['probe', $rehearsal, $today->copy()->addDay()->setTime(10, 0), $today->copy()->addDay()->setTime(14, 0), 'Probe'],
            ['fuehrung', $foyer, $today->copy()->addDay()->setTime(11, 0), $today->copy()->addDay()->setTime(12, 0), 'Führung hinter die Kulissen'],
        ];

        $created = 0;
        foreach ($plan as [$typeKey, $room, $start, $end, $name]) {
            if ($room === null) {
                continue;
            }
            $exists = Event::query()
                ->where('project_id', $project->id)
                ->where('eventName', $name)
                ->whereDate('start_time', $start->toDateString())
                ->exists();
            $roomBusy = Event::query()
                ->where('room_id', $room->id)
                ->whereNull('deleted_at')
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();
            if ($exists || $roomBusy) {
                continue;
            }

            Event::create([
                'name' => $name,
                'eventName' => $name,
                'start_time' => $start,
                'end_time' => $end,
                'admission_time' => $typeKey === 'vorstellung' ? '18:45' : null,
                'allDay' => false,
                'event_type_id' => $context->eventType($typeKey)?->id,
                'room_id' => $room->id,
                'project_id' => $project->id,
                'user_id' => $context->plannerUser()->id,
                'event_status_id' => $context->eventStatus('Bestätigt')?->id,
                'accepted' => true,
                'audience' => $typeKey === 'vorstellung',
                'is_loud' => false,
                'is_planning' => false,
                'occupancy_option' => false,
            ]);
            $created++;
        }

        $this->command?->info(sprintf(
            'Heute-Programm: %d Termine für "%s" am %s/%s ergänzt.',
            $created,
            $project->name,
            $today->format('d.m.'),
            $today->copy()->addDay()->format('d.m.')
        ));
    }

    /** Bevorzugt ein Projekt, das aktuell läuft; sonst das nächstliegende Demo-Projekt. */
    private function pickProject(Carbon $today): ?Project
    {
        $demoProjects = Project::query()
            ->get()
            ->filter(static fn (Project $project) => in_array(
                DemoProjectPools::archetypeForProjectName($project->name),
                ['eigenproduktion', 'gastspiel', 'konzert'],
                true
            ));

        $running = $demoProjects->first(static function (Project $project) use ($today) {
            $first = $project->events()->min('start_time');
            $last = $project->events()->max('end_time');

            return $first !== null
                && Carbon::parse($first)->lte($today->copy()->addDays(10))
                && Carbon::parse($last)->gte($today);
        });

        return $running ?? $demoProjects->first();
    }
}
