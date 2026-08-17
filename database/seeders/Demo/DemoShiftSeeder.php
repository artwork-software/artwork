<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Erzeugt Schichten + Funktions-Bedarf für die Termine der Demo-Projekte —
 * nach Gewerke-Matrix je Termintyp. Idempotent: Termine mit Schichten werden
 * übersprungen.
 */
class DemoShiftSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    private DemoContext $context;

    public function run(): void
    {
        $this->context = new DemoContext();
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();

        $demoProjects = Project::query()
            ->get(['id', 'name'])
            ->filter(static fn (Project $project) => DemoProjectPools::archetypeForProjectName($project->name) !== null);

        $events = Event::query()
            ->whereIn('project_id', $demoProjects->pluck('id'))
            ->where('start_time', '>=', $windowStart)
            ->where('start_time', '<', $windowEnd)
            ->where('is_planning', false)
            ->orderBy('start_time')
            ->get();

        $archetypes = $demoProjects->pluck('name', 'id')
            ->map(static fn (string $name) => DemoProjectPools::archetypeForProjectName($name));

        $createdShifts = 0;
        foreach ($events as $event) {
            if ($event->shifts()->exists()) {
                continue;
            }
            $matrixKey = $this->matrixKeyForEvent($event, $archetypes[$event->project_id] ?? null);
            if ($matrixKey === null) {
                continue;
            }
            $createdShifts += $this->createShiftsForEvent($event, $matrixKey);
        }

        $this->command?->info(sprintf('Schichten: %d neu erzeugt (inkl. Funktions-Bedarf).', $createdShifts));
    }

    private function matrixKeyForEvent(Event $event, ?string $archetype): ?string
    {
        $type = $this->context->eventTypes()->firstWhere('id', $event->event_type_id);

        return match ($type?->name) {
            'Vorstellung' => $archetype === 'eigenproduktion' ? 'vorstellung_gross' : 'vorstellung',
            'Generalprobe' => 'generalprobe',
            'Probe' => $event->eventName === 'Endprobe' ? 'endprobe' : null,
            'Aufbau' => $event->eventName === 'Aufbau durch Kunde' ? null : 'aufbau',
            'Abbau' => 'abbau',
            'Sonderveranstaltung' => 'sonderveranstaltung',
            default => null,
        };
    }

    private function createShiftsForEvent(Event $event, string $matrixKey): int
    {
        $rng = new DemoRandom('shifts|' . $event->id);
        $eventDay = Carbon::parse($event->start_time)->startOfDay();
        $created = 0;

        foreach (DemoProjectPools::SHIFT_MATRICES[$matrixKey] as $craftKey => $demands) {
            $craft = $this->context->craft($craftKey);
            if ($craft === null) {
                continue;
            }

            [$startTime, $endTime, $breakMinutes, $endDayOffset] = $craftKey === 'einlass'
                ? DemoProjectPools::FRONT_OF_HOUSE_TIMES
                : DemoProjectPools::SHIFT_TIMES[$matrixKey];

            $shift = Shift::create([
                'event_id' => $event->id,
                'start_date' => $eventDay->toDateString(),
                'end_date' => $eventDay->copy()->addDays($endDayOffset)->toDateString(),
                'event_start_day' => $eventDay->toDateString(),
                'event_end_day' => $eventDay->copy()->addDays($endDayOffset)->toDateString(),
                'start' => $startTime,
                'end' => $endTime,
                'break_minutes' => $breakMinutes,
                'craft_id' => $craft->id,
                'room_id' => $event->room_id,
                'project_id' => $event->project_id,
                'description' => $rng->chance(0.5) ? $rng->pick(DemoDataPools::SHIFT_DESCRIPTIONS) : null,
                'shift_uuid' => (string) Str::uuid(),
                'is_committed' => false,
                'shift_group_id' => $this->shiftGroupIdFor($matrixKey),
            ]);
            $created++;

            foreach ($demands as $qualificationKey => $count) {
                $qualification = $this->context->qualification($qualificationKey);
                if ($qualification === null) {
                    continue;
                }
                $shift->shiftsQualifications()->create([
                    'shift_qualification_id' => $qualification->id,
                    'value' => $count,
                ]);
            }
        }

        return $created;
    }

    private function shiftGroupIdFor(string $matrixKey): ?int
    {
        $groupName = match ($matrixKey) {
            'aufbau' => 'Aufbau',
            'abbau' => 'Abbau',
            'generalprobe', 'endprobe' => 'Probe',
            default => 'Vorstellung',
        };

        return $this->context->shiftGroup($groupName)?->id;
    }
}
