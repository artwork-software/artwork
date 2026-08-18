<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Raum-Auslastung: füllt die Lücken ALLER Räume (auch der vorbestehenden wie
 * "Villa Elisabeth Saal", "Container B", "Studio 2") mit realitätsnahen
 * Hausprogramm-Formaten — Gastproben, Workshops, Offene Bühne, Kindertheater,
 * Vermietungen — bis jede Woche eine Ziel-Belegung erreicht. Die Termine
 * laufen über Reihen-Projekte und bekommen so über den DemoShiftSeeder
 * automatisch besetzbare Schichten. Idempotent über Terminname+Raum+Tag.
 */
class DemoRoomFillSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    /** Belegte Tage je Raum und Woche, die angestrebt werden. */
    private const TARGET_DAYS_STAGE = [5, 6];
    private const TARGET_DAYS_REHEARSAL = [4, 5];

    private DemoContext $context;

    /** @var array<int, array<string, true>> roomId => [Y-m-d => true] */
    private array $occupiedDays = [];

    public function run(): void
    {
        $this->context = new DemoContext();
        $random = new DemoRandom('room-fill');
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->subDay()->endOfDay();

        $seriesProjects = $this->seriesProjects();
        $rooms = $this->fillableRooms();
        $this->loadOccupancy($rooms, $windowStart, $windowEnd);

        $created = 0;
        foreach ($rooms as $room) {
            $isRehearsal = $this->isRehearsalLike($room);
            $rng = $random->fork('room|' . $room->id);

            $weekStart = $windowStart->copy()->startOfWeek();
            while ($weekStart->lte($windowEnd)) {
                $created += $this->fillWeek(
                    $room,
                    $weekStart,
                    $windowStart,
                    $windowEnd,
                    $isRehearsal,
                    $seriesProjects,
                    $rng->fork($weekStart->format('o-W'))
                );
                $weekStart->addWeek();
            }
        }

        $this->command?->info(sprintf(
            'Raum-Auslastung: %d Hausprogramm-Termine in %d Räumen ergänzt.',
            $created,
            $rooms->count()
        ));
    }

    /** @return Collection<int, Project> keyed by series key */
    private function seriesProjects(): Collection
    {
        $projects = collect();
        foreach (DemoProjectPools::FILL_SERIES as $key => $definition) {
            $project = Project::firstOrCreate(
                ['name' => $definition['project']],
                [
                    'artists' => $definition['artists'],
                    'color' => $definition['color'],
                    'icon' => 'IconCalendarEvent',
                    'is_group' => false,
                    'user_id' => $this->context->adminUser()->id,
                    'state' => $this->context->projectState('Läuft')?->id,
                ]
            );
            if ($project->wasRecentlyCreated) {
                $manager = $this->context->demoUser('Deniz', 'Aydın') ?? $this->context->adminUser();
                $project->users()->syncWithoutDetaching([
                    $manager->id => ['is_manager' => true, 'can_write' => true, 'access_budget' => false, 'delete_permission' => false],
                ]);
            }
            $projects->put($key, $project);
        }

        return $projects;
    }

    /** Alle bespielbaren Räume — Werkstätten/Lager bewusst außen vor. */
    private function fillableRooms(): Collection
    {
        $workshopNames = collect(DemoDataPools::ROOMS)
            ->where('role', 'workshop')
            ->pluck('name');

        return Room::query()
            ->where(fn ($q) => $q->where('temporary', false)->orWhereNull('temporary'))
            ->whereNotIn('name', $workshopNames)
            ->orderBy('id')
            ->get();
    }

    private function isRehearsalLike(Room $room): bool
    {
        $rehearsalNames = collect(DemoDataPools::ROOMS)
            ->whereIn('role', ['rehearsal'])
            ->pluck('name');

        return $rehearsalNames->contains($room->name)
            || str_contains(mb_strtolower($room->name), 'probe')
            || str_contains(mb_strtolower($room->name), 'studio');
    }

    private function loadOccupancy(Collection $rooms, Carbon $start, Carbon $end): void
    {
        $events = Event::query()
            ->whereNull('deleted_at')
            ->whereIn('room_id', $rooms->pluck('id'))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->get(['room_id', 'start_time']);

        foreach ($events as $event) {
            $this->occupiedDays[$event->room_id][Carbon::parse($event->start_time)->toDateString()] = true;
        }
    }

    private function fillWeek(
        Room $room,
        Carbon $weekStart,
        Carbon $windowStart,
        Carbon $windowEnd,
        bool $isRehearsal,
        Collection $seriesProjects,
        DemoRandom $rng
    ): int {
        [$targetMin, $targetMax] = $isRehearsal ? self::TARGET_DAYS_REHEARSAL : self::TARGET_DAYS_STAGE;
        $target = $rng->int($targetMin, $targetMax);

        // Tage der Woche im Fenster, aufgeteilt in belegt/frei
        $freeDays = [];
        $occupiedCount = 0;
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->copy()->addDays($i);
            if ($day->lt($windowStart) || $day->gt($windowEnd)) {
                continue;
            }
            if (isset($this->occupiedDays[$room->id][$day->toDateString()])) {
                $occupiedCount++;
            } else {
                $freeDays[] = $day;
            }
        }

        $created = 0;
        $freeDays = $rng->shuffle($freeDays);
        while ($occupiedCount < $target && $freeDays !== []) {
            $day = array_shift($freeDays);
            $format = $this->pickFormat($rng, $isRehearsal);
            if ($format === null) {
                continue;
            }
            [$seriesKey, $typeKey, $name, $startTime, $endTime] = $format;

            $project = $seriesProjects->get($seriesKey);
            $eventType = $this->context->eventType($typeKey);
            if ($project === null || $eventType === null) {
                continue;
            }

            $exists = Event::query()
                ->where('room_id', $room->id)
                ->where('eventName', $name)
                ->whereDate('start_time', $day->toDateString())
                ->exists();
            if ($exists) {
                $occupiedCount++;
                continue;
            }

            Event::create([
                'name' => $name,
                'eventName' => $name,
                'start_time' => $day->copy()->setTimeFromTimeString($startTime),
                'end_time' => $day->copy()->setTimeFromTimeString($endTime),
                'admission_time' => $typeKey === 'vorstellung' ? '18:45' : null,
                'allDay' => false,
                'event_type_id' => $eventType->id,
                'room_id' => $room->id,
                'project_id' => $project->id,
                'user_id' => $this->context->plannerUser()->id,
                'event_status_id' => $this->context->eventStatus(
                    $day->isPast() ? 'Bestätigt' : (string) $rng->weighted(['Bestätigt' => 8, 'Angefragt' => 2])
                )?->id,
                'accepted' => true,
                'audience' => in_array($typeKey, ['vorstellung', 'sonderveranstaltung'], true),
                'is_loud' => false,
                'is_planning' => false,
                'occupancy_option' => false,
            ]);

            $this->occupiedDays[$room->id][$day->toDateString()] = true;
            $occupiedCount++;
            $created++;
        }

        return $created;
    }

    /** @return array{0: string, 1: string, 2: string, 3: string, 4: string}|null */
    private function pickFormat(DemoRandom $rng, bool $isRehearsal): ?array
    {
        $formats = collect(DemoProjectPools::FILL_FORMATS);
        // Probenräume: tagsüber-Formate (Gastproben, Workshops); Bühnen: alles
        $candidates = $isRehearsal
            ? $formats->filter(static fn (array $format) => $format[5] === 'day'
                && in_array($format[0], ['workshops', 'gastproben'], true))
            : $formats;

        if ($candidates->isEmpty()) {
            return null;
        }
        $picked = $rng->pick($candidates->values()->all());

        return [$picked[0], $picked[1], $picked[2], $picked[3], $picked[4]];
    }
}
