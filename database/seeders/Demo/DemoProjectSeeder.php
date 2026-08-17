<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Calendar\Models\DayRemark;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;

/**
 * Erzeugt pro Monat 6–8 Projekte aus Archetypen inkl. Tab-Inhalten (Team,
 * Attribute, Checklisten, Kommentare, Custom-Komponenten, Budget) und den
 * zugehörigen Terminen (kollisionfrei je Raum). Idempotent: Projektnamen sind
 * deterministisch aus dem absoluten Monat abgeleitet; Termine werden nur für
 * Projekte ohne Termine erzeugt.
 */
class DemoProjectSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;
    public bool $dryRun = false;

    private DemoContext $context;

    /** @var array<int, array<int, array{0: int, 1: int}>> roomId => [[startTs, endTs], ...] */
    private array $occupancy = [];

    private Carbon $windowStart;
    private Carbon $windowEnd;

    public function run(): void
    {
        $this->context = new DemoContext();
        $this->windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $this->windowEnd = $this->windowStart->copy()->addMonths($this->months)->subDay()->endOfDay();

        $this->loadOccupancy();

        for ($i = 0; $i < $this->months; $i++) {
            $month = $this->windowStart->copy()->addMonths($i);
            $this->seedMonth($month);
        }

        $this->seedFestival();
        $this->seedPlanningProjects();
        $this->seedDayRemarks();
    }

    /* -----------------------------------------------------------------
     | Monatsplan
     | ----------------------------------------------------------------- */

    private function seedMonth(Carbon $month): void
    {
        $rng = new DemoRandom('projects|' . $month->format('Y-m'));
        // Epoche 2026-01: hält die Indizes klein, damit frühe Monate die
        // Basisnamen ohne Suffix bekommen — deterministisch über alle Fenster.
        $absMonth = max(0, ($month->year - 2026) * 12 + ($month->month - 1));
        $planned = 0;

        foreach (DemoProjectPools::ARCHETYPES as $archetypeKey => $archetype) {
            $count = $rng->int($archetype['per_month'][0], $archetype['per_month'][1]);
            $pool = DemoProjectPools::PROJECT_POOLS[$archetypeKey];

            for ($slot = 0; $slot < $count; $slot++) {
                $globalIndex = $absMonth * 3 + $slot;
                $entry = $pool[$globalIndex % count($pool)];
                // Wiederholt sich ein Pool-Eintrag (kleine Pools, viele Monate),
                // macht ein Monats-Suffix den Namen eindeutig — deterministisch
                // über beliebige Zeitfenster hinweg.
                $cycle = intdiv($globalIndex, count($pool));
                $name = $entry['name'] . ($cycle > 0 ? ' (' . $this->germanMonth($month) . ')' : '');

                if ($this->dryRun) {
                    $this->command?->line(sprintf('  [dry-run] %s: %s', $month->format('Y-m'), $name));
                    $planned++;
                    continue;
                }

                $this->createProject($archetypeKey, $archetype, $entry, $name, $month, $rng->fork($name));
                $planned++;
            }
        }

        $this->command?->info(sprintf('%s: %d Projekte geplant.', $month->format('m/Y'), $planned));
    }

    /** @param array<string, mixed> $archetype @param array<string, mixed> $entry */
    private function createProject(
        string $archetypeKey,
        array $archetype,
        array $entry,
        string $name,
        Carbon $month,
        DemoRandom $rng
    ): void {
        $project = Project::firstOrCreate(
            ['name' => $name],
            [
                'artists' => $entry['artists'],
                'color' => $archetype['color'],
                'icon' => 'IconMasksTheater',
                'user_id' => $this->context->adminUser()->id,
                'number_of_participants' => (string) $rng->int(80, 600),
                'is_group' => false,
                'cost_center_id' => CostCenter::query()->where('name', $archetype['cost_center'])->value('id'),
            ]
        );

        if ($project->wasRecentlyCreated) {
            $this->seedAttributes($project, $archetype, $entry);
            $this->seedTeam($project, $rng);
            $this->seedShiftMeta($project, $archetypeKey);
            $this->seedChecklist($project, $archetypeKey, $month, $rng);
            $this->seedComments($project, $month, $rng);
            $this->seedBudget($project, $archetypeKey, $rng);
        }
        // idempotent (firstOrCreate je Komponente) — füllt auch bei bestehenden
        // Projekten nachträglich hinzugekommene Custom-Komponenten
        $this->seedComponentValues($project, $archetypeKey, $entry, $rng);

        if ($project->events()->doesntExist()) {
            $this->scheduleEvents($project, $archetypeKey, $month, $rng);
        }

        $this->updateProjectState($project);
    }

    /* -----------------------------------------------------------------
     | Tab-Inhalte
     | ----------------------------------------------------------------- */

    /** @param array<string, mixed> $archetype @param array<string, mixed> $entry */
    private function seedAttributes(Project $project, array $archetype, array $entry): void
    {
        $categoryId = Category::query()->where('name', $archetype['category'])->value('id');
        $genreId = Genre::query()->where('name', $entry['genre'])->value('id');
        $sectorId = Sector::query()->where('name', $archetype['sector'])->value('id');

        if ($categoryId) {
            $project->categories()->syncWithoutDetaching([$categoryId]);
        }
        if ($genreId) {
            $project->genres()->syncWithoutDetaching([$genreId]);
        }
        if ($sectorId) {
            $project->sectors()->syncWithoutDetaching([$sectorId]);
        }
    }

    private function seedTeam(Project $project, DemoRandom $rng): void
    {
        $team = [];

        $manager = $this->context->demoUser('Miriam', 'Petersen') ?? $this->context->adminUser();
        $altManager = $this->context->demoUser('Deniz', 'Aydın');
        if ($altManager !== null && $rng->chance(0.5)) {
            $manager = $altManager;
        }
        $team[$manager->id] = ['is_manager' => true, 'can_write' => true, 'access_budget' => true, 'delete_permission' => true];

        $budgetUser = $this->context->demoUser('Helga', 'Storm');
        if ($budgetUser !== null) {
            $team[$budgetUser->id] ??= ['is_manager' => false, 'can_write' => false, 'access_budget' => true, 'delete_permission' => false];
        }

        $crewPool = $this->context->demoUsers()
            ->filter(static fn (User $user) => $user->can_work_shifts)
            ->pluck('id')
            ->all();
        foreach ($rng->pickMany($crewPool, $rng->int(2, 4)) as $userId) {
            $team[$userId] ??= ['is_manager' => false, 'can_write' => true, 'access_budget' => false, 'delete_permission' => false];
        }

        $project->users()->syncWithoutDetaching($team);
    }

    private function seedShiftMeta(Project $project, string $archetypeKey): void
    {
        $relevantTypeIds = collect(['vorstellung', 'generalprobe', 'aufbau', 'abbau'])
            ->map(fn (string $key) => $this->context->eventType($key)?->id)
            ->filter()
            ->all();
        $project->shiftRelevantEventTypes()->syncWithoutDetaching($relevantTypeIds);

        $contacts = collect([
            $this->context->demoUser('Frank', 'Ohlsen'),
            $this->context->demoUser('Svenja', 'Carstens'),
        ])->filter()->pluck('id')->all();
        if ($contacts !== []) {
            $project->shift_contact()->syncWithoutDetaching($contacts);
        }

        $project->update([
            'shift_description' => 'Treffpunkt Bühneneingang, Funk Kanal 2. '
                . 'Pausenregelung nach Absprache mit der Abendspielleitung.',
        ]);
    }

    /** @param array<string, mixed> $entry */
    private function seedComponentValues(Project $project, string $archetypeKey, array $entry, DemoRandom $rng): void
    {
        $values = [
            ['Sparte', 'DropDown', ['selected' => $entry['genre']]],
            ['Technische Anforderungen', 'TextArea', ['text' => nl2br(DemoProjectPools::TECH_REQUIREMENTS[$archetypeKey])]],
            ['Barrierefrei', 'Checkbox', ['checked' => $rng->chance(0.5)]],
            ['Pressematerial', 'Link', ['text' => 'https://presse.testhaus.artwork.software/' . $project->id]],
        ];
        if ($archetypeKey === 'vermietung') {
            $values[] = ['Ansprechperson extern', 'TextField', ['text' => 'Kim Sander, +49 40 555 123 00']];
        }

        foreach ($values as [$name, $type, $data]) {
            $component = Component::query()->where('name', $name)->where('type', $type)->first();
            if ($component === null) {
                continue;
            }
            ProjectComponentValue::firstOrCreate(
                ['project_id' => $project->id, 'component_id' => $component->id],
                ['data' => $data]
            );
        }

        // Auch alle ÜBRIGEN Custom-Komponenten der Installation befüllen (gewachsene
        // Konfigurationen haben eigene Felder) — sonst wirken die Tabs leer.
        $this->fillRemainingCustomComponents($project, $rng);
    }

    /** Generische, plausible Werte für alle unbefüllten Custom-Komponenten des Systems. */
    private function fillRemainingCustomComponents(Project $project, DemoRandom $rng): void
    {
        $textPool = [
            'Wird in der nächsten Produktionsbesprechung geklärt.',
            'Abgestimmt mit der Technischen Leitung.',
            'Details siehe Produktionsmappe.',
            'Rücksprache mit der Produktionsleitung läuft.',
        ];

        $customComponents = Component::query()
            ->whereIn('type', ['Checkbox', 'TextField', 'TextArea', 'DropDown', 'Link'])
            ->get();

        foreach ($customComponents as $component) {
            $componentRng = $rng->fork('component|' . $component->id);
            $data = match ($component->type) {
                'Checkbox' => ['checked' => $componentRng->chance(0.5)],
                'TextField' => ['text' => $componentRng->pick($textPool)],
                'TextArea' => ['text' => $componentRng->pick($textPool)],
                'Link' => ['text' => 'https://testhaus.artwork.software/info/' . $project->id],
                'DropDown' => (function () use ($component, $componentRng) {
                    $options = collect($component->data['options'] ?? [])
                        ->pluck('value')
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->values();

                    return $options->isNotEmpty()
                        ? ['selected' => $componentRng->pick($options->all())]
                        : null;
                })(),
                default => null,
            };
            if ($data === null) {
                continue;
            }

            ProjectComponentValue::firstOrCreate(
                ['project_id' => $project->id, 'component_id' => $component->id],
                ['data' => $data]
            );
        }
    }

    private function seedChecklist(Project $project, string $archetypeKey, Carbon $month, DemoRandom $rng): void
    {
        $template = DemoProjectPools::CHECKLIST_TEMPLATES[$archetypeKey] ?? null;
        if ($template === null) {
            return;
        }

        $checklist = Checklist::firstOrCreate(
            ['project_id' => $project->id, 'name' => $template['name']],
            ['user_id' => $this->context->adminUser()->id, 'private' => false]
        );
        if (!$checklist->wasRecentlyCreated) {
            return;
        }

        $isPast = $month->copy()->endOfMonth()->isPast();
        $doneChance = $isPast ? 0.9 : 0.3;
        $responsibles = $this->context->demoUsers()->pluck('id')->all();

        foreach ($template['tasks'] as $index => $taskName) {
            $done = $rng->chance($doneChance);
            $task = $checklist->tasks()->create([
                'name' => $taskName,
                'description' => '',
                'order' => $index + 1,
                'done' => $done,
                'done_at' => $done ? $month->copy()->startOfMonth()->addDays($rng->int(1, 20)) : null,
                'deadline' => $month->copy()->startOfMonth()->addDays($rng->int(5, 25))->setTime(12, 0),
                'user_id' => $done ? $rng->pick($responsibles) : null,
            ]);
            $task->task_users()->syncWithoutDetaching([$rng->pick($responsibles)]);
        }
    }

    private function seedComments(Project $project, Carbon $month, DemoRandom $rng): void
    {
        $teamIds = $project->users()->pluck('users.id')->all();
        if ($teamIds === []) {
            $teamIds = [$this->context->adminUser()->id];
        }

        foreach ($rng->pickMany(DemoProjectPools::COMMENTS, $rng->int(2, 4)) as $index => $text) {
            $comment = Comment::firstOrCreate(
                ['project_id' => $project->id, 'text' => $text],
                ['user_id' => $rng->pick($teamIds)]
            );
            if ($comment->wasRecentlyCreated) {
                $timestamp = $month->copy()->startOfMonth()->addDays($rng->int(0, 25))->setTime($rng->int(8, 18), 15);
                $comment->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->saveQuietly();
            }
        }
    }

    /* -----------------------------------------------------------------
     | Budget
     | ----------------------------------------------------------------- */

    private function seedBudget(Project $project, string $archetypeKey, DemoRandom $rng): void
    {
        if ($project->table()->exists()) {
            return;
        }
        app(BudgetService::class)->generateBasicBudgetValues($project);

        $table = $project->table()->first();
        if ($table === null) {
            return;
        }
        $columns = $table->columns()->orderBy('position')->get();
        $valueColumn = $columns->firstWhere('position', 3) ?? $columns->last();

        $costMain = $table->mainPositions()->where('type', 'BUDGET_TYPE_COST')->first()
            ?? $table->mainPositions()->orderBy('id')->first();
        $earningMain = $table->mainPositions()->where('type', 'BUDGET_TYPE_EARNING')->first()
            ?? $table->mainPositions()->orderBy('id', 'desc')->first();

        $costRows = [
            ['5400', '4711', 'Honorare Gäste', $rng->int(6, 18) * 1000],
            ['5410', '4711', 'Technik-Leihmaterial', $rng->int(2, 8) * 500],
            ['5420', '4711', 'Marketing & Druck', $rng->int(2, 6) * 500],
        ];
        $earningRows = [
            ['8400', '4711', 'Kartenverkauf', $rng->int(8, 24) * 1000],
            ['8410', '4711', 'Förderung Kulturstiftung', $rng->int(5, 15) * 1000],
        ];

        $this->fillMainPosition($costMain, 'Ausgaben', 'Produktionskosten', $costRows, $columns, $valueColumn);
        $this->fillMainPosition($earningMain, 'Einnahmen', 'Erlöse & Förderung', $earningRows, $columns, $valueColumn);

        // Eine Geldquelle mit Verknüpfung an die Förderzeile (nur bei Eigenproduktionen)
        if ($archetypeKey === 'eigenproduktion') {
            $moneySource = MoneySource::query()->where('name', 'Kulturstiftung Nord – Projektförderung')->first();
            if ($moneySource === null) {
                $moneySource = new MoneySource([
                    'name' => 'Kulturstiftung Nord – Projektförderung',
                    'amount' => 75000,
                    'source_name' => 'Kulturstiftung Nord',
                    'description' => 'Rahmenförderung für Eigenproduktionen der Spielzeit.',
                    'users' => json_encode([$this->context->adminUser()->id]),
                ]);
                $moneySource->creator_id = $this->context->adminUser()->id;
                $moneySource->save();
            }
            $project->moneySources()->syncWithoutDetaching([$moneySource->id]);
        }
    }

    /** @param array<int, array{0: string, 1: string, 2: string, 3: int}> $rows */
    private function fillMainPosition(
        $mainPosition,
        string $mainName,
        string $subName,
        array $rows,
        $columns,
        $valueColumn
    ): void {
        if ($mainPosition === null) {
            return;
        }
        $mainPosition->update(['name' => $mainName]);

        $subPosition = $mainPosition->subPositions()->orderBy('position')->first();
        if ($subPosition === null) {
            return;
        }
        $subPosition->update(['name' => $subName]);

        $existingRows = $subPosition->subPositionRows()->orderBy('position')->get();

        foreach ($rows as $index => [$kto, $kst, $label, $amount]) {
            $row = $existingRows->get($index) ?? $subPosition->subPositionRows()->create([
                'commented' => false,
                'position' => $index + 1,
                'order' => $index + 1,
            ]);

            foreach ($columns as $column) {
                $value = match ((int) $column->position) {
                    0 => $kto,
                    1 => $kst,
                    2 => $label,
                    3 => number_format($amount, 2, ',', ''),
                    default => '0,00',
                };
                $cell = $row->cells()->firstOrCreate(
                    ['column_id' => $column->id],
                    ['value' => $value, 'verified_value' => '', 'linked_money_source_id' => null]
                );
                if (!$cell->wasRecentlyCreated) {
                    $cell->update(['value' => $value]);
                }
            }
        }
    }

    /* -----------------------------------------------------------------
     | Termine
     | ----------------------------------------------------------------- */

    private function loadOccupancy(): void
    {
        $events = Event::query()
            ->whereNull('deleted_at')
            ->whereNotNull('room_id')
            ->where('start_time', '<', $this->windowEnd)
            ->where('end_time', '>', $this->windowStart)
            ->get(['room_id', 'start_time', 'end_time']);

        foreach ($events as $event) {
            $this->occupancy[$event->room_id][] = [
                Carbon::parse($event->start_time)->getTimestamp(),
                Carbon::parse($event->end_time)->getTimestamp(),
            ];
        }
    }

    private function isFree(?Room $room, Carbon $start, Carbon $end): bool
    {
        if ($room === null) {
            return true;
        }
        foreach ($this->occupancy[$room->id] ?? [] as [$busyStart, $busyEnd]) {
            if ($start->getTimestamp() < $busyEnd && $end->getTimestamp() > $busyStart) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $options */
    private function createEvent(
        Project $project,
        string $typeKey,
        ?Room $room,
        Carbon $start,
        Carbon $end,
        DemoRandom $rng,
        array $options = []
    ): ?Event {
        if (!$this->isFree($room, $start, $end)) {
            return null;
        }

        $statusName = $options['status']
            ?? ($start->isPast()
                ? 'Bestätigt'
                : (string) $rng->weighted(['Bestätigt' => 7, 'Angefragt' => 2, 'Optioniert' => 1]));

        $event = Event::create([
            'name' => $options['name'] ?? null,
            'eventName' => $options['name'] ?? null,
            'description' => $options['description'] ?? null,
            'start_time' => $start,
            'end_time' => $end,
            'admission_time' => $options['admission'] ?? null,
            'allDay' => $options['allDay'] ?? false,
            'event_type_id' => $this->context->eventType($typeKey)?->id,
            'room_id' => $room?->id,
            'project_id' => $project->id,
            'user_id' => $this->context->plannerUser()->id,
            'event_status_id' => $this->context->eventStatus($statusName)?->id,
            'accepted' => true,
            'audience' => in_array($typeKey, ['vorstellung', 'sonderveranstaltung'], true),
            'is_loud' => $typeKey === 'vorstellung' && $rng->chance(0.3),
            'is_planning' => $options['is_planning'] ?? false,
            'occupancy_option' => false,
        ]);

        if ($room !== null) {
            $this->occupancy[$room->id][] = [$start->getTimestamp(), $end->getTimestamp()];
        }

        return $event;
    }

    private function scheduleEvents(Project $project, string $archetypeKey, Carbon $month, DemoRandom $rng): void
    {
        $stageRole = DemoProjectPools::ARCHETYPES[$archetypeKey]['stage_role'];
        $stage = $this->pickStage($stageRole, $month, $rng);
        if ($stage === null) {
            return;
        }

        match ($archetypeKey) {
            'eigenproduktion' => $this->scheduleEigenproduktion($project, $stage, $month, $rng),
            'gastspiel' => $this->scheduleGastspiel($project, $stage, $month, $rng),
            'konzert' => $this->scheduleKonzert($project, $stage, $month, $rng),
            'vermietung' => $this->scheduleVermietung($project, $stage, $month, $rng),
            default => null,
        };
    }

    private function pickStage(string $role, Carbon $month, DemoRandom $rng): ?Room
    {
        $rooms = $this->context->roomsByRole($role);
        if ($rooms->isEmpty()) {
            return null;
        }

        return $rng->pick($rooms->all());
    }

    /** Findet einen Ankertag, an dem der Abendslot der Bühne frei ist. */
    private function findAnchor(Room $stage, Carbon $month, DemoRandom $rng, int $minDay = 8, int $maxDay = 26): ?Carbon
    {
        $candidates = [];
        for ($day = $minDay; $day <= min($maxDay, $month->daysInMonth); $day++) {
            $date = $month->copy()->startOfMonth()->addDays($day - 1);
            if (in_array($date->isoWeekday(), DemoProjectPools::SHOW_WEEKDAYS, true)) {
                $candidates[] = $date;
            }
        }
        foreach ($rng->shuffle($candidates) as $candidate) {
            if ($this->isFree($stage, $candidate->copy()->setTime(17, 0), $candidate->copy()->setTime(23, 30))) {
                return $candidate;
            }
        }

        return null;
    }

    private function show(Project $project, Room $stage, Carbon $day, DemoRandom $rng, string $name): ?Event
    {
        return $this->createEvent(
            $project,
            'vorstellung',
            $stage,
            $day->copy()->setTime(19, 30),
            $day->copy()->setTime(22, 0),
            $rng,
            [
                'name' => $name,
                'admission' => '18:45',
                // Vorstellungen sind fast immer fix — "Angefragt"-Premieren wirken falsch
                'status' => $day->isPast() || $rng->chance(0.85) ? 'Bestätigt' : 'Optioniert',
            ]
        );
    }

    private function scheduleEigenproduktion(Project $project, Room $stage, Carbon $month, DemoRandom $rng): void
    {
        $premiere = $this->findAnchor($stage, $month, $rng, 10, 24);
        if ($premiere === null) {
            return;
        }

        $rehearsalRoom = $rng->pick($this->context->roomsByRole('rehearsal')->all());

        // Probenphase (3 Wochen vor Premiere, Probenraum)
        for ($offset = 21; $offset >= 5; $offset--) {
            $day = $premiere->copy()->subDays($offset);
            if ($day->isoWeekday() > 5 || !$rng->chance(0.55)) {
                continue;
            }
            $evening = $rng->chance(0.4);
            $this->createEvent(
                $project,
                'probe',
                $rehearsalRoom,
                $day->copy()->setTime($evening ? 18 : 10, 0),
                $day->copy()->setTime($evening ? 22 : 14, 0),
                $rng,
                ['name' => 'Probe']
            );
        }

        // Aufbau + Endproben auf der Bühne
        foreach ([4, 3] as $offset) {
            $day = $premiere->copy()->subDays($offset);
            $this->createEvent($project, 'aufbau', $stage, $day->copy()->setTime(9, 0), $day->copy()->setTime(18, 0), $rng, ['name' => 'Aufbau Bühnenbild']);
            $this->createEvent($project, 'probe', $stage, $day->copy()->setTime(18, 30), $day->copy()->setTime(22, 0), $rng, ['name' => 'Endprobe']);
        }

        $this->createEvent(
            $project,
            'generalprobe',
            $stage,
            $premiere->copy()->subDay()->setTime(18, 0),
            $premiere->copy()->subDay()->setTime(21, 30),
            $rng,
            ['name' => 'Generalprobe']
        );

        // Premiere + Vorstellungsserie
        $this->show($project, $stage, $premiere, $rng, 'Premiere');
        $showCount = $rng->int(3, 6);
        $lastShow = $premiere;
        $created = 0;
        for ($offset = 1; $offset <= 16 && $created < $showCount; $offset++) {
            $day = $premiere->copy()->addDays($offset);
            if (!in_array($day->isoWeekday(), DemoProjectPools::SHOW_WEEKDAYS, true)) {
                continue;
            }
            $created++;
            $name = $created === $showCount
                ? 'Derniere'
                : (DemoProjectPools::EVENT_NAME_SHOW[$created] ?? ($created + 1) . '. Vorstellung');
            if ($this->show($project, $stage, $day, $rng, $name) !== null) {
                $lastShow = $day;
            }
        }

        // Abbau in der Nacht nach der Derniere
        $this->createEvent(
            $project,
            'abbau',
            $stage,
            $lastShow->copy()->setTime(22, 30),
            $lastShow->copy()->addDay()->setTime(2, 0),
            $rng,
            ['name' => 'Abbau']
        );

        // Eine Raumanfrage ohne Raum (Zusatzprobe) für die "Termine ohne Raum"-Ansicht
        $this->createEvent(
            $project,
            'probe',
            null,
            $premiere->copy()->subDays(6)->setTime(14, 0),
            $premiere->copy()->subDays(6)->setTime(17, 0),
            $rng,
            ['name' => 'Zusatzprobe (Raum offen)', 'status' => 'Angefragt']
        );
    }

    private function scheduleGastspiel(Project $project, Room $stage, Carbon $month, DemoRandom $rng): void
    {
        $firstShow = $this->findAnchor($stage, $month, $rng);
        if ($firstShow === null) {
            return;
        }
        $setupDay = $firstShow->copy()->subDay();

        $this->createEvent($project, 'anlieferung', $stage, $setupDay->copy()->setTime(8, 0), $setupDay->copy()->setTime(10, 0), $rng, ['name' => 'Anlieferung Compagnie']);
        $this->createEvent($project, 'aufbau', $stage, $setupDay->copy()->setTime(10, 0), $setupDay->copy()->setTime(18, 0), $rng, ['name' => 'Aufbau & Einrichtung']);
        $this->createEvent($project, 'probe', $stage, $firstShow->copy()->setTime(15, 0), $firstShow->copy()->setTime(17, 0), $rng, ['name' => 'Soundcheck / Spacing']);

        $showCount = $rng->int(1, 3);
        $lastShow = $firstShow;
        for ($i = 0; $i < $showCount; $i++) {
            $day = $firstShow->copy()->addDays($i);
            $name = $showCount === 1 ? 'Vorstellung' : ($i + 1) . '. Vorstellung';
            if ($this->show($project, $stage, $day, $rng, $name) !== null) {
                $lastShow = $day;
            }
        }

        $this->createEvent($project, 'abbau', $stage, $lastShow->copy()->setTime(22, 30), $lastShow->copy()->addDay()->setTime(1, 30), $rng, ['name' => 'Abbau & Verladung']);
    }

    private function scheduleKonzert(Project $project, Room $stage, Carbon $month, DemoRandom $rng): void
    {
        $day = $this->findAnchor($stage, $month, $rng);
        if ($day === null) {
            return;
        }

        $this->createEvent($project, 'aufbau', $stage, $day->copy()->setTime(9, 0), $day->copy()->setTime(13, 0), $rng, ['name' => 'Aufbau Backline']);
        $this->createEvent($project, 'probe', $stage, $day->copy()->setTime(16, 0), $day->copy()->setTime(17, 30), $rng, ['name' => 'Soundcheck']);
        $this->createEvent(
            $project,
            'vorstellung',
            $stage,
            $day->copy()->setTime(20, 0),
            $day->copy()->setTime(22, 30),
            $rng,
            ['name' => 'Konzert', 'admission' => '19:15']
        );
        $this->createEvent($project, 'abbau', $stage, $day->copy()->setTime(22, 30), $day->copy()->addDay()->setTime(0, 30), $rng, ['name' => 'Abbau']);
    }

    private function scheduleVermietung(Project $project, Room $stage, Carbon $month, DemoRandom $rng): void
    {
        $day = $this->findAnchor($stage, $month, $rng);
        if ($day === null) {
            return;
        }

        $this->createEvent($project, 'aufbau', $stage, $day->copy()->setTime(14, 0), $day->copy()->setTime(17, 0), $rng, ['name' => 'Aufbau durch Kunde']);
        $this->createEvent(
            $project,
            'sonderveranstaltung',
            $stage,
            $day->copy()->setTime(18, 0),
            $day->copy()->setTime(23, 0),
            $rng,
            ['name' => 'Veranstaltung (Vermietung)']
        );
    }

    /* -----------------------------------------------------------------
     | Festival, Planungsprojekte, Tagesbemerkungen
     | ----------------------------------------------------------------- */

    private function seedFestival(): void
    {
        if ($this->months < 3 || $this->dryRun) {
            return;
        }
        $festivalMonth = $this->windowStart->copy()->addMonths(min(3, $this->months - 1));
        $rng = new DemoRandom('festival|' . $festivalMonth->format('Y-m'));
        $yearLabel = $festivalMonth->format('y');

        $group = Project::firstOrCreate(
            ['name' => sprintf(DemoProjectPools::FESTIVAL['group_name'], $yearLabel)],
            [
                'artists' => DemoProjectPools::FESTIVAL['artists'],
                'color' => DemoProjectPools::FESTIVAL['color'],
                'icon' => 'IconConfetti',
                'is_group' => true,
                'user_id' => $this->context->adminUser()->id,
                'cost_center_id' => CostCenter::query()->where('name', DemoProjectPools::FESTIVAL['cost_center'])->value('id'),
            ]
        );

        foreach (DemoProjectPools::FESTIVAL['sub_projects'] as $index => $subDefinition) {
            $subName = sprintf($subDefinition['name'], $yearLabel);
            $sub = Project::firstOrCreate(
                ['name' => $subName],
                [
                    'artists' => $subDefinition['artists'],
                    'color' => DemoProjectPools::FESTIVAL['color'],
                    'icon' => 'IconConfetti',
                    'is_group' => false,
                    'user_id' => $this->context->adminUser()->id,
                    'cost_center_id' => $group->cost_center_id,
                ]
            );
            $sub->groups()->syncWithoutDetaching([$group->id]);

            if ($sub->wasRecentlyCreated) {
                $this->seedTeam($sub, $rng->fork($subName));
                $this->seedShiftMeta($sub, 'eigenproduktion');
            }

            if ($sub->events()->doesntExist()) {
                $stage = $this->pickStage($subDefinition['stage_role'], $festivalMonth, $rng);
                $anchor = $stage !== null ? $this->findAnchor($stage, $festivalMonth, $rng, 5, 20) : null;
                if ($stage === null || $anchor === null) {
                    continue;
                }
                $anchor = $anchor->copy()->addDays($index); // Festival-Tage staffeln
                $this->createEvent($sub, 'aufbau', $stage, $anchor->copy()->setTime(9, 0), $anchor->copy()->setTime(16, 0), $rng, ['name' => 'Festival-Aufbau']);
                $this->createEvent(
                    $sub,
                    'vorstellung',
                    $stage,
                    $anchor->copy()->setTime(19, 0),
                    $anchor->copy()->setTime(22, 30),
                    $rng,
                    ['name' => 'Festival: Vorstellung', 'admission' => '18:15']
                );
            }

            $this->updateProjectState($sub);
        }

        $this->updateProjectState($group);
        $this->command?->info(sprintf('Festival-Projektgruppe "%s" mit %d Unterprojekten angelegt.', $group->name, count(DemoProjectPools::FESTIVAL['sub_projects'])));
    }

    private function seedPlanningProjects(): void
    {
        if ($this->dryRun) {
            return;
        }
        $rng = new DemoRandom('planning|' . $this->windowStart->format('Y-m'));
        $futureMonth = $this->windowStart->copy()->addMonths(max(0, $this->months - 2));

        foreach (DemoProjectPools::PLANNING_PROJECTS as $index => $definition) {
            $name = str_contains($definition['name'], '%s')
                ? sprintf($definition['name'], $futureMonth->copy()->addYear()->format('y') . '/' . $futureMonth->copy()->addYears(2)->format('y'))
                : $definition['name'];

            $project = Project::firstOrCreate(
                ['name' => $name],
                [
                    'artists' => $definition['artists'],
                    'color' => '#64748b',
                    'icon' => 'IconBulb',
                    'is_group' => false,
                    'user_id' => $this->context->adminUser()->id,
                    'state' => $this->context->projectState('In Planung')?->id,
                ]
            );

            if ($project->events()->doesntExist()) {
                $stage = $this->pickStage($index === 0 ? 'main_stage' : 'second_stage', $futureMonth, $rng);
                if ($stage === null) {
                    continue;
                }
                $anchor = $this->findAnchor($stage, $futureMonth, $rng);
                if ($anchor === null) {
                    continue;
                }
                $this->createEvent(
                    $project,
                    'probe',
                    $stage,
                    $anchor->copy()->setTime(10, 0),
                    $anchor->copy()->setTime(16, 0),
                    $rng,
                    ['name' => 'Platzhalter Planung', 'is_planning' => true, 'status' => 'Angefragt']
                );
            }
        }
        $this->command?->info('Planungsprojekte mit Planungskalender-Terminen angelegt.');
    }

    private function seedDayRemarks(): void
    {
        if ($this->dryRun) {
            return;
        }
        $rng = new DemoRandom('day-remarks|' . $this->windowStart->format('Y-m'));
        $adminId = $this->context->adminUser()->id;

        foreach ($rng->pickMany(DemoProjectPools::DAY_REMARKS, 3) as $index => $remark) {
            $date = $this->windowStart->copy()
                ->addMonths($rng->int(0, max(0, $this->months - 1)))
                ->startOfMonth()
                ->addDays($rng->int(3, 24));
            DayRemark::firstOrCreate(
                ['date' => $date->toDateString()],
                ['remark' => $remark, 'created_by' => $adminId]
            );
        }
    }

    private function germanMonth(Carbon $month): string
    {
        $names = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        return $names[$month->month] . ' ' . $month->year;
    }

    private function updateProjectState(Project $project): void
    {
        $first = $project->events()->min('start_time');
        $last = $project->events()->max('end_time');
        if ($first === null) {
            return;
        }

        $now = Carbon::now();
        $stateName = Carbon::parse($last)->isPast()
            ? 'Abgeschlossen'
            : (Carbon::parse($first)->isFuture() ? 'Bestätigt' : 'Läuft');
        $stateId = $this->context->projectState($stateName)?->id;
        if ($stateId !== null && $project->state !== $stateId) {
            $project->update(['state' => $stateId]);
        }
    }
}
