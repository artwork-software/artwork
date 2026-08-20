<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * BI-Zahlen für die Demo-Vorstellungen: Besucher, verkaufte Tickets und
 * Erlöse je Vorstellung (Vergangenheit = Ist, Zukunft = Plan). Kapazität
 * kommt aus rooms.capacity (DemoStructureSeeder); die BI-Event-Tags werden
 * über den System-Command sichergestellt.
 */
class DemoBiSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    /** Durchschnittlicher Ticketpreis je Archetyp (für plausible Erlöse). */
    private const TICKET_PRICES = [
        'eigenproduktion' => 24,
        'gastspiel' => 28,
        'konzert' => 19,
        'vermietung' => 0,
    ];

    public function run(): void
    {
        // Termintypen → BI-Tags ("Vorstellung" etc.), idempotenter System-Command
        Artisan::call('artwork:add-bi-event-type-tags');

        $context = new DemoContext();
        $random = new DemoRandom('bi-data');
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();

        $demoProjects = Project::query()
            ->get(['id', 'name'])
            ->filter(static fn (Project $project) => DemoProjectPools::archetypeForProjectName($project->name) !== null);
        $archetypes = $demoProjects->pluck('name', 'id')
            ->map(static fn (string $name) => DemoProjectPools::archetypeForProjectName($name));

        $performanceTypeIds = collect(['vorstellung', 'sonderveranstaltung'])
            ->map(static fn (string $key) => $context->eventType($key)?->id)
            ->filter()
            ->all();

        $events = Event::query()
            ->whereIn('project_id', $demoProjects->pluck('id'))
            ->whereIn('event_type_id', $performanceTypeIds)
            ->where('is_planning', false)
            ->where('start_time', '>=', $windowStart)
            ->where('start_time', '<', $windowEnd)
            ->with('room:id,capacity')
            ->get();

        // Ohne bi_project_data (Modus "pro Termin") ignoriert das Dashboard die
        // Termin-Zahlen — je Demo-Projekt beide Scopes konfigurieren.
        $projectDataCreated = 0;
        foreach ($demoProjects as $project) {
            $archetype = $archetypes[$project->id] ?? 'eigenproduktion';
            $rng = $random->fork('project|' . $project->id);
            foreach (['actual', 'plan'] as $scope) {
                $projectData = BiProjectData::firstOrCreate(
                    ['project_id' => $project->id, 'scope' => $scope],
                    [
                        'visitor_mode' => 'per_event',
                        'sold_tickets_mode' => 'per_event',
                        'revenue_mode' => 'per_event',
                        'costs_total' => match ($archetype) {
                            'eigenproduktion' => $rng->int(18, 45) * 1000,
                            'gastspiel' => $rng->int(8, 20) * 1000,
                            'konzert' => $rng->int(3, 9) * 1000,
                            default => $rng->int(1, 4) * 1000,
                        },
                        'is_own_production' => $archetype === 'eigenproduktion',
                        'is_new_production' => $archetype === 'eigenproduktion',
                    ]
                );
                if ($projectData->wasRecentlyCreated) {
                    $projectDataCreated++;
                }
            }
        }
        if ($projectDataCreated > 0) {
            $this->command?->info(sprintf('BI: Projekt-Konfiguration (pro Termin) für %d Einträge angelegt.', $projectDataCreated));
        }

        $created = 0;
        foreach ($events as $event) {
            if (BiEventData::query()->where('event_id', $event->id)->exists()) {
                continue;
            }

            $rng = $random->fork('event|' . $event->id);
            $capacity = (int) ($event->room?->capacity ?? 200);
            $archetype = $archetypes[$event->project_id] ?? 'eigenproduktion';
            $price = self::TICKET_PRICES[$archetype] ?? 20;

            $isPast = Carbon::parse($event->start_time)->isPast();
            // Auslastung: Vergangenheit 55–98 % (Ist), Zukunft 35–85 % (VVK-Stand als Plan)
            $rate = $isPast ? $rng->int(55, 98) / 100 : $rng->int(35, 85) / 100;
            $sold = (int) round($capacity * $rate);
            $visitors = $isPast ? max(0, $sold - $rng->int(0, (int) ($sold * 0.06))) : $sold;

            BiEventData::create([
                'project_id' => $event->project_id,
                'event_id' => $event->id,
                'scope' => $isPast ? 'actual' : 'plan',
                'visitors' => $visitors,
                'sold_tickets' => $sold,
                'revenue' => $price > 0 ? round($sold * $price * (1 - $rng->int(0, 15) / 100), 2) : 0,
            ]);
            $created++;
        }

        // Dashboard-Cache invalidieren (Schreibzugriffe bumpen die Version)
        Cache::increment('bi_dashboard_version');

        $this->command?->info(sprintf('BI: Besucher-/Erlösdaten für %d Vorstellungen erzeugt.', $created));
    }
}
