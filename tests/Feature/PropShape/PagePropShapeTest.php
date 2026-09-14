<?php

namespace Tests\Feature\PropShape;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkerShiftPlanFilter;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsPropShape;
use Tests\Feature\FeatureTestCase;

/**
 * Struktur-Snapshots + N+1-Wächter für die Seiten, deren Payloads am 14.09.2026
 * verschlankt wurden. Jeder Test rendert die Seite über eine gemeinsame Testwelt mit
 * mehr Zeilen je Entität als die Wiederholungs-Schwelle (siehe AssertsPropShape).
 *
 * Snapshot aktualisieren (nach bewusster Strukturänderung und Konsumenten-Check):
 *   UPDATE_PROP_SNAPSHOTS=1 php artisan test tests/Feature/PropShape
 */
final class PagePropShapeTest extends FeatureTestCase
{
    use AssertsPropShape;

    private const ROWS = 6;

    private User $admin;
    private User $worker;
    private Craft $craft;
    private ShiftQualification $qualification;
    private Project $project;
    private Room $room;
    private Freelancer $freelancer;
    private ServiceProvider $serviceProvider;
    private Carbon $start;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->actingAsAdmin();
        $this->start = Carbon::now()->startOfMonth()->addDays(2)->startOfDay();
        $this->buildWorld();
    }

    private function buildWorld(): void
    {
        $this->qualification = ShiftQualification::factory()->create();
        $this->craft = Craft::factory()->create(['universally_applicable' => false]);
        $universal = Craft::factory()->create(['universally_applicable' => true]);
        $this->craft->craftShiftPlaner()->attach($this->admin->id);
        $this->craft->qualifications()->attach($this->qualification->id);

        // Personen: ROWS+1 Mitarbeitende (alle im Gewerk, mit Funktion), Freelancer, Dienstleister
        $users = User::factory()->count(self::ROWS + 1)->create(['can_work_shifts' => true]);
        foreach ($users as $user) {
            $user->assignedCrafts()->attach([$this->craft->id, $universal->id]);
            $user->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $this->craft->id]);
        }
        $this->worker = $users->first();
        $this->worker->assignedCrafts()->syncWithoutDetaching([$this->craft->id]);

        $freelancers = Freelancer::factory()->count(self::ROWS)->create(['can_work_shifts' => true]);
        foreach ($freelancers as $freelancer) {
            $freelancer->assignedCrafts()->attach($this->craft->id);
            $freelancer->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $this->craft->id]);
        }
        $this->freelancer = $freelancers->first();

        $providers = ServiceProvider::factory()->count(self::ROWS)->create(['can_work_shifts' => true]);
        foreach ($providers as $provider) {
            $provider->assignedCrafts()->attach($this->craft->id);
        }
        $this->serviceProvider = $providers->first();

        // Räume: ROWS Räume mit Admin, Ersteller, Kategorie, Eigenschaft, Nebenraum
        $area = Area::factory()->create();
        $category = RoomCategory::query()->create(['name' => 'Bühne']);
        $attribute = RoomAttribute::factory()->create();
        $rooms = Room::factory()->count(self::ROWS)->create(['area_id' => $area->id, 'user_id' => $this->admin->id]);
        foreach ($rooms as $index => $room) {
            $room->users()->attach($this->admin->id, ['is_admin' => true, 'can_request' => false]);
            $room->categories()->attach($category->id);
            $room->attributes()->attach($attribute->id);
            $room->adjoining_rooms()->attach($rooms[($index + 1) % self::ROWS]->id);
        }
        $this->room = $rooms->first();

        // Projekt in einer Gruppe, mit Team
        $this->project = Project::factory()->create();
        $group = Project::factory()->create(['is_group' => true]);
        $this->project->groups()->attach($group->id);
        $this->project->users()->attach($this->admin->id);

        // Termine: ROWS im Raum + ROWS ohne Raum (Planungskalender/Kalender-Panel)
        $eventType = EventType::factory()->create(['relevant_for_shift' => true]);
        $eventType->verifiers()->attach($this->admin->id);
        for ($i = 0; $i < self::ROWS; $i++) {
            $day = $this->start->copy()->addDays($i);
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'room_id' => $this->room->id,
                'event_type_id' => $eventType->id,
                'user_id' => $this->admin->id,
                'start_time' => $day->copy()->setTime(18, 0),
                'end_time' => $day->copy()->setTime(22, 0),
            ]);
            Event::factory()->create([
                'project_id' => $this->project->id,
                'room_id' => null,
                'event_type_id' => $eventType->id,
                'user_id' => $this->admin->id,
                'start_time' => $day->copy()->setTime(10, 0),
                'end_time' => $day->copy()->setTime(12, 0),
            ]);

            // Schicht am Termin + freie Schicht am selben Tag; Kolleg:innen dazu
            foreach ([$event->id, null] as $eventId) {
                $shift = Shift::factory()->create([
                    'event_id' => $eventId,
                    'project_id' => $this->project->id,
                    'room_id' => $this->room->id,
                    'craft_id' => $this->craft->id,
                    'start_date' => $day->toDateString(),
                    'end_date' => $day->toDateString(),
                    'start' => $eventId ? '18:00:00' : '08:00:00',
                    'end' => $eventId ? '22:00:00' : '12:00:00',
                    'break_minutes' => 30,
                ]);
                ShiftsQualifications::query()->create([
                    'shift_id' => $shift->id,
                    'shift_qualification_id' => $this->qualification->id,
                    'value' => 4,
                ]);
                foreach ([$this->worker, $users[1 + ($i % self::ROWS)]] as $user) {
                    $shift->users()->attach($user->id, [
                        'shift_qualification_id' => $this->qualification->id,
                        'shift_count' => 1,
                    ]);
                }
                $shift->freelancer()->attach($this->freelancer->id, [
                    'shift_qualification_id' => $this->qualification->id,
                    'shift_count' => 1,
                ]);
                $shift->serviceProvider()->attach($this->serviceProvider->id, [
                    'shift_qualification_id' => $this->qualification->id,
                    'shift_count' => 1,
                ]);
            }
        }

        // Checklisten mit Aufgaben (Zuständige, teils erledigt)
        for ($i = 0; $i < self::ROWS; $i++) {
            $checklist = Checklist::factory()->create([
                'project_id' => $this->project->id,
                'user_id' => $this->admin->id,
                'private' => false,
            ]);
            $checklist->users()->attach($this->admin->id);
            for ($t = 0; $t < 3; $t++) {
                $task = Task::factory()->create([
                    'checklist_id' => $checklist->id,
                    'order' => $t,
                    'done' => $t === 0,
                    'user_id' => $t === 0 ? $this->admin->id : null,
                    'done_at' => $t === 0 ? now() : null,
                    'deadline' => $this->start->copy()->addDays($t),
                ]);
                $task->task_users()->attach($this->admin->id);
            }
        }

        // Zeiträume der eingeloggten Person auf die Testwoche legen
        $end = $this->start->copy()->addDays(self::ROWS - 1);
        UserWorkerShiftPlanFilter::query()->create([
            'user_id' => $this->admin->id,
            'start_date' => $this->start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);
        $filterTypes = [
            UserFilterTypes::SHIFT_LIST_VIEW_FILTER,
            UserFilterTypes::CALENDAR_FILTER,
            UserFilterTypes::PLANNING_FILTER,
        ];
        foreach ($filterTypes as $type) {
            $this->admin->userFilters()->updateOrCreate(
                ['filter_type' => $type->value],
                ['start_date' => $this->start->toDateString(), 'end_date' => $end->toDateString()]
            );
        }
    }

    private function shiftTab(): ProjectTab
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $component = Component::create([
            'name' => 'Schichten',
            'type' => ProjectTabComponentEnum::SHIFT_TAB->value,
            'data' => [],
        ]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 1]);

        return $tab;
    }

    /**
     * @param array<int, string> $allowRepeated bekannte Mehrfach-Muster der Seite (Altlast)
     */
    private function page(string $url, array $allowRepeated = []): TestResponse
    {
        return $this->assertNoRepeatedQueryPatterns(fn () => $this->get($url)->assertOk(), 4, $allowRepeated);
    }

    #[Test]
    public function own_operation_plan(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('user.operationPlan', $this->worker)),
            'own-operation-plan',
            [
                'daysWithData',
                'crafts',
                'user_to_edit',
                'workTimeTarget',
                'projectAssignments',
                'pendingWorkTimeChangeRequests',
            ]
        );
    }

    #[Test]
    public function user_account_shift_plan_tab(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('user.edit.shiftplan', $this->worker)),
            'user-shift-plan-tab',
            ['daysWithData', 'crafts', 'user_to_edit', 'workTimeTarget', 'vacations', 'availabilities']
        );
    }

    #[Test]
    public function freelancer_profile(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('freelancer.show', $this->freelancer)),
            'freelancer-profile',
            ['daysWithData', 'crafts', 'freelancer']
        );
    }

    #[Test]
    public function service_provider_profile(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('service_provider.show', $this->serviceProvider)),
            'service-provider-profile',
            ['daysWithData', 'crafts', 'serviceProvider']
        );
    }

    #[Test]
    public function project_shift_tab(): void
    {
        $tab = $this->shiftTab();

        $this->assertPropShapeMatchesSnapshot(
            // Altlast der Projektseite (nicht Teil der Payload-Diät): Komponenten/Disclosures
            // werden je Tab geladen — hier bewusst erlaubt
            $this->page(
                route('projects.tab', ['project' => $this->project->id, 'projectTab' => $tab->id]),
                ['from `components`', 'from `disclosure_components`']
            ),
            'project-shift-tab',
            ['crafts', 'headerObject', 'shiftQualifications', 'currentUserCrafts']
        );
    }

    #[Test]
    public function shift_plan_list_view(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('shifts.plan.list-view')),
            'shift-plan-list-view',
            ['crafts', 'groupedShifts', 'shiftQualifications', 'currentUserCrafts']
        );
    }

    #[Test]
    public function notifications(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('notifications.index')),
            'notifications',
            ['rooms', 'eventTypes', 'projects']
        );
    }

    #[Test]
    public function planning_calendar(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('planning-event-calendar.index')),
            'planning-calendar',
            ['eventsWithoutRoom', 'rooms']
        );
    }

    #[Test]
    public function calendar(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page('/calendar/view'),
            'calendar',
            ['eventsWithoutRoom', 'rooms']
        );
    }

    #[Test]
    public function areas_management(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('areas.management')),
            'areas-management',
            ['areas', 'room_categories', 'room_attributes']
        );
    }

    #[Test]
    public function own_tasks(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page(route('tasks.own')),
            'own-tasks',
            ['public_checklists', 'private_checklists']
        );
    }

    #[Test]
    public function dashboard(): void
    {
        $this->assertPropShapeMatchesSnapshot(
            $this->page('/dashboard'),
            'dashboard',
            ['tasks', 'eventTypes']
        );
    }
}
