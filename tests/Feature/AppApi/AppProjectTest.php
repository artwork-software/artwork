<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event as EventModel;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use App\Settings\ShiftSettings;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppProjectTest extends TestCase
{
    use CreatesUserShifts;

    protected function setUp(): void
    {
        parent::setUp();
        // Neutralize the real broadcaster but keep all other events intact.
        Event::fake([UpdateProjectComponentData::class]);
    }

    private function createTabWithComponent(
        string $type = 'TextField',
        array $componentAttributes = [],
    ): array {
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $component = Component::create(array_merge([
            'name' => 'Field',
            'type' => $type,
            'data' => ['label' => 'Field'],
        ], $componentAttributes));
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 0,
        ]);

        return [$tab, $component];
    }

    #[Test]
    public function projectEndpointsRequireAuthentication(): void
    {
        $project = Project::factory()->create();
        [$tab, $component] = $this->createTabWithComponent();

        $this->getJson(route('app.v1.projects'))->assertUnauthorized();
        $this->getJson(route('app.v1.projects.show', $project))->assertUnauthorized();
        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))->assertUnauthorized();
        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['text' => 'x']],
        )->assertUnauthorized();
    }

    #[Test]
    public function projectListOnlyContainsProjectsTheUserIsAMemberOf(): void
    {
        $user = User::factory()->create();
        $memberProject = Project::factory()->create(['name' => 'Member project']);
        $memberProject->users()->attach($user->id);
        Project::factory()->create(['name' => 'Foreign project']);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects'))
            ->assertOk()
            ->assertJsonCount(1, 'projects')
            ->assertJsonPath('projects.0.id', $memberProject->id)
            ->assertJsonPath('projects.0.name', 'Member project')
            ->assertJsonPath('projects.0.team_count', 1);
    }

    #[Test]
    public function projectListContainsAllProjectsWithTheViewProjectsPermission(): void
    {
        Project::factory()->create(['name' => 'A project']);
        Project::factory()->create(['name' => 'B project']);

        $this->actingAsApiUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->getJson(route('app.v1.projects'))
            ->assertOk()
            ->assertJsonCount(2, 'projects')
            ->assertJsonPath('projects.0.name', 'A project')
            ->assertJsonPath('projects.1.name', 'B project');
    }

    #[Test]
    public function projectListIncludesTheState(): void
    {
        $user = User::factory()->create();
        $state = ProjectState::factory()->create(['name' => 'Running', 'color' => '#3E8E6D']);
        $project = Project::factory()->create(['state' => $state->id]);
        $project->users()->attach($user->id);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects'))
            ->assertOk()
            ->assertJsonPath('projects.0.state.name', 'Running')
            ->assertJsonPath('projects.0.state.color', '#3E8E6D');
    }

    #[Test]
    public function projectDetailIsForbiddenForNonMembers(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.projects.show', Project::factory()->create()))
            ->assertForbidden();
    }

    #[Test]
    public function projectDetailContainsTeamAndOmitsWebOnlyTabs(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['is_manager' => true]);

        [$customTab] = $this->createTabWithComponent();

        // A tab containing only a web-only component (the budget spreadsheet)
        // is omitted entirely — the strip shows only renderable tabs.
        $budgetTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $budgetComponent = Component::create(['name' => 'Budget', 'type' => 'BudgetTab', 'data' => []]);
        ComponentInTab::create([
            'project_tab_id' => $budgetTab->id,
            'component_id' => $budgetComponent->id,
            'order' => 0,
        ]);

        Passport::actingAs($user, ['app']);

        $response = $this->getJson(route('app.v1.projects.show', $project))->assertOk();

        $response->assertJsonPath('project.id', $project->id)
            ->assertJsonPath('project.team.0.id', $user->id)
            ->assertJsonPath('project.team.0.is_manager', true);
        // The suite runs on a seeded database — assert relative to it instead
        // of expecting an exact tab list.
        $tabIds = array_column($response->json('project.tabs'), 'id');
        $this->assertContains($customTab->id, $tabIds);
        $this->assertNotContains($budgetTab->id, $tabIds);
    }

    #[Test]
    public function tabPayloadContainsComponentsWithValuesAndWritability(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $component] = $this->createTabWithComponent();
        ProjectComponentValue::create([
            'project_id' => $project->id,
            'component_id' => $component->id,
            'data' => ['text' => 'Stored value'],
        ]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonPath('tab.id', $tab->id)
            ->assertJsonCount(1, 'components')
            ->assertJsonPath('components.0.component_id', $component->id)
            ->assertJsonPath('components.0.type', 'text-field')
            ->assertJsonPath('components.0.value.text', 'Stored value')
            ->assertJsonPath('components.0.is_writable', true);
    }

    #[Test]
    public function tabPayloadHidesComponentsTheUserMayNotSee(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent('TextField', ['permission_type' => 'someSeeSomeEdit']);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonCount(0, 'components');
    }

    #[Test]
    public function writeProjectsPermissionDoesNotRevealComponentsRestrictedToListedViewers(): void
    {
        $project = Project::factory()->create();
        [$tab, $component] = $this->createTabWithComponent('TextField', ['permission_type' => 'someSeeSomeEdit']);
        $user = $this->actingAsApiUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonCount(0, 'components');

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['text' => 'x']],
        )->assertForbidden();

        $component->users()->attach($user->id, ['can_write' => false]);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonCount(1, 'components')
            ->assertJsonPath('components.0.is_writable', true);
    }

    #[Test]
    public function tabPayloadMarksAllSeeSomeEditComponentsReadOnlyForNonMembers(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent('TextField', ['permission_type' => 'allSeeSomeEdit']);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonCount(1, 'components')
            ->assertJsonPath('components.0.is_writable', false);
    }

    #[Test]
    public function tabPayloadContainsDisclosureChildren(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $disclosure] = $this->createTabWithComponent('DisclosureComponent');
        $child = Component::create(['name' => 'Child', 'type' => 'Checkbox', 'data' => ['label' => 'Child']]);
        DisclosureComponents::create([
            'disclosure_id' => $disclosure->id,
            'component_id' => $child->id,
            'order' => 0,
        ]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonPath('components.0.type', 'disclosure')
            ->assertJsonPath('components.0.is_writable', false)
            ->assertJsonPath('components.0.children.0.component_id', $child->id)
            ->assertJsonPath('components.0.children.0.is_writable', true);
    }

    #[Test]
    public function tabInvisibleToTheUserIsForbidden(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent();
        $tab->update(['visible_for_all' => false]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))->assertForbidden();
    }

    #[Test]
    public function componentValueCanBeUpdatedAndTextIsNormalized(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $component] = $this->createTabWithComponent();

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['text' => "line one\nline two"]],
        )
            ->assertOk()
            ->assertJsonPath('component_id', $component->id);

        $value = ProjectComponentValue::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->first();
        $this->assertSame("line one\nline two", $value->data['text']);
        Event::assertDispatched(UpdateProjectComponentData::class);
    }

    #[Test]
    public function updatingKeepsASingleValueRowPerProjectAndComponent(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $component] = $this->createTabWithComponent('Checkbox');
        $existing = ProjectComponentValue::create([
            'project_id' => $project->id,
            'component_id' => $component->id,
            'data' => ['checked' => false],
        ]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['checked' => true]],
        )
            ->assertOk()
            ->assertJsonPath('value.checked', true);

        $this->assertSame(1, ProjectComponentValue::query()->count());
        $this->assertTrue((bool) $existing->fresh()->data['checked']);
    }

    #[Test]
    public function updateRejectsComponentsOfUnwritableTypes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $component] = $this->createTabWithComponent('Title');

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['title' => 'x']],
        )->assertForbidden();
    }

    #[Test]
    public function updateRejectsComponentsOutsideTheRequestedTab(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent();
        [, $foreignComponent] = $this->createTabWithComponent();

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $foreignComponent]),
            ['data' => ['text' => 'x']],
        )->assertNotFound();
    }

    #[Test]
    public function updateRejectsUsersWithoutComponentWritePermission(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $component] = $this->createTabWithComponent(
            'TextField',
            ['permission_type' => 'someSeeSomeEdit'],
        );
        $component->users()->attach($user->id, ['can_write' => false]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['text' => 'x']],
        )->assertForbidden();
    }

    #[Test]
    public function updateAllowsComponentMembersWithWritePermission(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $component] = $this->createTabWithComponent(
            'TextField',
            ['permission_type' => 'someSeeSomeEdit'],
        );
        $component->users()->attach($user->id, ['can_write' => true]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['text' => 'allowed']],
        )->assertOk();
    }

    #[Test]
    public function updateCanWriteDisclosureChildValues(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab, $disclosure] = $this->createTabWithComponent('DisclosureComponent');
        $child = Component::create(['name' => 'Child', 'type' => 'Checkbox', 'data' => ['label' => 'Child']]);
        DisclosureComponents::create([
            'disclosure_id' => $disclosure->id,
            'component_id' => $child->id,
            'order' => 0,
        ]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $child]),
            ['data' => ['checked' => true]],
        )->assertOk();
    }

    #[Test]
    public function calendarTabComponentContainsTheProjectsEvents(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent('CalendarTab');
        $event = EventModel::factory()->create([
            'project_id' => $project->id,
            'start_time' => now()->setTime(10, 0),
            'end_time' => now()->setTime(12, 0),
            'is_planning' => false,
        ]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonPath('components.0.type', 'calendar')
            ->assertJsonPath('components.0.is_writable', false)
            ->assertJsonPath('components.0.value.days.0.date', now()->toDateString())
            ->assertJsonPath('components.0.value.days.0.events.0.id', $event->id);
    }

    #[Test]
    public function bulkBodyScheduleComponentContainsEventsAndNormalizesEmptyData(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        // Real instances store system components with data = [] — the app
        // contract requires data to be an object or null, never a JSON array.
        [$tab] = $this->createTabWithComponent('BulkBody', ['data' => []]);
        $event = EventModel::factory()->create([
            'project_id' => $project->id,
            'start_time' => now()->setTime(10, 0),
            'end_time' => now()->setTime(12, 0),
            'is_planning' => false,
        ]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonPath('components.0.type', 'calendar')
            ->assertJsonPath('components.0.data', null)
            ->assertJsonPath('components.0.value.days.0.events.0.id', $event->id);
    }

    #[Test]
    public function shiftTabComponentContainsTheProjectsShiftsWithStaffing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent('ShiftTab');
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonPath('components.0.value.days.0.date', now()->toDateString())
            ->assertJsonPath('components.0.value.days.0.shifts.0.id', $shift->id)
            ->assertJsonPath('components.0.value.days.0.shifts.0.assigned_count', 1)
            ->assertJsonPath('components.0.value.days.0.shifts.0.workers.0.name', $user->full_name)
            ->assertJsonPath('components.0.value.days.0.shifts.0.workers.0.type', 'user');
    }

    #[Test]
    public function checklistTabHidesForeignPrivateChecklists(): void
    {
        // A viewer with the global permission but no project membership sees
        // public checklists, never someone else's private ones.
        $owner = User::factory()->create();
        $project = Project::factory()->create();
        [$tab] = $this->createTabWithComponent('ChecklistComponent');
        Checklist::factory()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'name' => 'Public list',
            'private' => false,
        ]);
        Checklist::factory()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'name' => 'Private list',
            'private' => true,
        ]);

        $this->actingAsApiUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertOk()
            ->assertJsonCount(1, 'components.0.value.checklists')
            ->assertJsonPath('components.0.value.checklists.0.name', 'Public list');
    }

    #[Test]
    public function commentTabComponentContainsProjectCommentsNewestFirst(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent('CommentTab');
        Comment::create(['project_id' => $project->id, 'user_id' => $user->id, 'text' => 'First']);
        Comment::create(['project_id' => $project->id, 'user_id' => $user->id, 'text' => 'Second']);

        Passport::actingAs($user, ['app']);

        $response = $this->getJson(route('app.v1.projects.tab', [$project, $tab]))->assertOk();

        $this->assertSame(
            ['Second', 'First'],
            array_column($response->json('components.0.value.comments'), 'text'),
        );
        $this->assertSame($user->full_name, $response->json('components.0.value.comments.0.author'));
    }

    #[Test]
    public function eventsCanBeCreatedWithTheProperPermission(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $project->users()->attach($user->id);

        $this->postJson(route('app.v1.projects.events.store', $project), [
            'name' => 'Bauprobe',
            'start' => now()->setTime(10, 0)->toIso8601String(),
            'end' => now()->setTime(12, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => null,
            'event_type_id' => EventModel::factory()->create()->event_type_id,
        ])
            ->assertCreated()
            ->assertJsonPath('event.name', 'Bauprobe')
            ->assertJsonPath('event.can_edit', true);

        $this->assertSame(1, $project->events()->where('eventName', 'Bauprobe')->count());
    }

    #[Test]
    public function eventCreationIsForbiddenWithoutEventPermissions(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);

        Passport::actingAs($user, ['app']);

        $this->postJson(route('app.v1.projects.events.store', $project), [
            'name' => 'Bauprobe',
            'start' => now()->toIso8601String(),
            'end' => now()->addHour()->toIso8601String(),
            'all_day' => false,
            'event_type_id' => EventModel::factory()->create()->event_type_id,
        ])->assertForbidden();
    }

    #[Test]
    public function eventsWithoutRoomNeedTheRightToBookWithoutRoom(): void
    {
        $project = Project::factory()->create();
        // Anfragerecht erlaubt das Anlegen (EventPolicy::create), aber keinen Termin ohne Raum
        $user = $this->actingAsApiUserWith(PermissionEnum::EVENT_REQUEST->value);
        $project->users()->attach($user->id);

        $this->postJson(route('app.v1.projects.events.store', $project), [
            'name' => 'Bauprobe',
            'start' => now()->setTime(10, 0)->toIso8601String(),
            'end' => now()->setTime(12, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => null,
            'event_type_id' => EventModel::factory()->create()->event_type_id,
        ])->assertForbidden();

        $this->assertSame(0, $project->events()->where('eventName', 'Bauprobe')->count());
    }

    #[Test]
    public function roomChangeWithoutDirectBookingRightBecomesARoomRequest(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $oldRoom = Room::factory()->create(['everyone_can_book' => false]);
        $newRoom = Room::factory()->create(['everyone_can_book' => false]);
        $event = EventModel::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'room_id' => $oldRoom->id,
            'occupancy_option' => false,
            'accepted' => true,
            'is_planning' => false,
        ]);
        $this->mock(RoomRequestNotificationService::class)
            ->shouldReceive('notifyRoomAdmins')
            ->once()
            ->withArgs(fn (EventModel $notified): bool => $notified->room?->id === $newRoom->id);

        Passport::actingAs($user, ['app']);

        $this->patchJson(route('app.v1.projects.events.update', [$project, $event]), [
            'name' => 'Umzug',
            'start' => now()->setTime(14, 0)->toIso8601String(),
            'end' => now()->setTime(16, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => $newRoom->id,
            'event_type_id' => $event->event_type_id,
        ])->assertOk();

        $event->refresh();
        $this->assertSame($newRoom->id, $event->room_id);
        $this->assertTrue((bool) $event->occupancy_option);
        $this->assertFalse((bool) $event->accepted);
    }

    #[Test]
    public function roomChangeWithDirectBookingRightStaysABooking(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $newRoom = Room::factory()->create(['everyone_can_book' => false]);
        $newRoom->users()->attach($user->id, ['is_admin' => true]);
        $event = EventModel::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'room_id' => Room::factory()->create()->id,
            'occupancy_option' => false,
            'accepted' => true,
            'is_planning' => false,
        ]);
        $this->mock(RoomRequestNotificationService::class)->shouldNotReceive('notifyRoomAdmins');

        Passport::actingAs($user, ['app']);

        $this->patchJson(route('app.v1.projects.events.update', [$project, $event]), [
            'name' => 'Umzug',
            'start' => now()->setTime(14, 0)->toIso8601String(),
            'end' => now()->setTime(16, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => $newRoom->id,
            'event_type_id' => $event->event_type_id,
        ])->assertOk();

        $event->refresh();
        $this->assertSame($newRoom->id, $event->room_id);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function eventCreatorsCanUpdateTheirEvent(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $event = EventModel::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(route('app.v1.projects.events.update', [$project, $event]), [
            'name' => 'Umbenannt',
            'start' => now()->setTime(14, 0)->toIso8601String(),
            'end' => now()->setTime(16, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => null,
            'event_type_id' => $event->event_type_id,
        ])
            ->assertOk()
            ->assertJsonPath('event.name', 'Umbenannt');

        $this->assertSame('Umbenannt', $event->fresh()->eventName);
    }

    #[Test]
    public function eventUpdateRejectsEventsOfOtherProjects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $foreignEvent = EventModel::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(route('app.v1.projects.events.update', [$project, $foreignEvent]), [
            'name' => 'X',
            'start' => now()->toIso8601String(),
            'end' => now()->addHour()->toIso8601String(),
            'all_day' => false,
            'event_type_id' => $foreignEvent->event_type_id,
        ])->assertNotFound();
    }

    #[Test]
    public function shiftsCanBeUpdatedByShiftPlanners(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);

        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shift]), [
            'day' => now()->toDateString(),
            'start' => '16:00',
            'end' => '22:00',
            'break_minutes' => 45,
            'description' => 'Treffpunkt Bühneneingang',
            'craft_id' => $shift->craft_id,
            'qualifications' => [],
        ])
            ->assertOk()
            ->assertJsonPath('shift.start', '16:00')
            ->assertJsonPath('shift.break_minutes', 45);

        $this->assertSame('16:00', $shift->fresh()->start);
    }

    #[Test]
    public function shiftPlannersCanCreateAShiftWithItsRequirements(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $craft = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $response = $this->postJson(route('app.v1.projects.shifts.store', $project), [
            'day' => now()->toDateString(),
            'start' => '22:00',
            // Ends after midnight — end_date must roll over to the next day.
            'end' => '02:00',
            'break_minutes' => 30,
            'description' => 'Nachtschicht',
            'craft_id' => $craft->id,
            'room_id' => Room::factory()->create()->id,
            'qualifications' => [
                ['shift_qualification_id' => $qualification->id, 'value' => 2],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('shift.date', now()->toDateString())
            ->assertJsonPath('shift.end_date', now()->addDay()->toDateString())
            ->assertJsonPath('shift.required_count', 2)
            ->assertJsonPath('shift.qualifications.0.required', 2);

        $this->assertSame(1, $project->shifts()->count());
        $this->assertSame('Nachtschicht', $response->json('shift.description'));
    }

    #[Test]
    public function shiftUpdateChangesDateCraftAndRequirements(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);
        $craft = Craft::factory()->create();
        $qualification = $shift->users->first()->pivot->shift_qualification_id;
        ShiftsQualifications::create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification,
            'value' => 1,
        ]);
        $newDay = now()->addWeek()->toDateString();

        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shift]), [
            'day' => $newDay,
            'start' => '09:00',
            'end' => '17:00',
            'break_minutes' => 60,
            'description' => null,
            'craft_id' => $craft->id,
            'room_id' => null,
            'qualifications' => [
                ['shift_qualification_id' => $qualification, 'value' => 3],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('shift.date', $newDay)
            ->assertJsonPath('shift.start', '09:00')
            ->assertJsonPath('shift.craft.id', $craft->id)
            ->assertJsonPath('shift.qualifications.0.required', 3);
    }

    #[Test]
    public function requirementsBelowTheBookedPeopleAreClampedToTheBookedCount(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);
        $qualification = $shift->users->first()->pivot->shift_qualification_id;
        ShiftsQualifications::create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification,
            'value' => 1,
        ]);

        // Same rule as the web: the requirement never drops below the people
        // already booked — the request succeeds and the value is clamped.
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shift]), [
            'day' => now()->toDateString(),
            'start' => '18:00',
            'end' => '23:00',
            'break_minutes' => 30,
            'craft_id' => $shift->craft_id,
            'qualifications' => [
                ['shift_qualification_id' => $qualification, 'value' => 0],
            ],
        ])->assertOk();

        $this->assertSame(1, $shift->shiftsQualifications()->count());
        $this->assertSame(
            1,
            (int) $shift->shiftsQualifications()->where('shift_qualification_id', $qualification)->value('value'),
        );
    }

    #[Test]
    public function deletingAShiftCascadesItsAssignments(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);

        // Same behaviour as the web delete path: assignments are removed with
        // the shift instead of blocking the deletion.
        $this->deleteJson(route('app.v1.projects.shifts.destroy', [$project, $shift]))
            ->assertNoContent();

        $this->assertSame(0, $project->shifts()->count());
        $this->assertSame(0, $shift->users()->count());
    }

    #[Test]
    public function shiftsWithoutEventNeedARoomLikeInTheWeb(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $payload = [
            'day' => now()->toDateString(),
            'start' => '10:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'craft_id' => Craft::factory()->create()->id,
            'room_id' => null,
            'qualifications' => [],
        ];

        $this->postJson(route('app.v1.projects.shifts.store', $project), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');
        $this->assertSame(0, $project->shifts()->count());

        $room = Room::factory()->create();
        $shiftId = $this->postJson(
            route('app.v1.projects.shifts.store', $project),
            [...$payload, 'room_id' => $room->id],
        )->assertCreated()->json('shift.id');

        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shiftId]), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');
        $this->assertSame($room->id, Shift::query()->find($shiftId)->room_id);

        // Schichten am Termin hängen am Raum des Termins — dort bleibt der Raum optional
        $eventShift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);
        $this->patchJson(
            route('app.v1.projects.shifts.update', [$project, $eventShift]),
            [...$payload, 'craft_id' => $eventShift->craft_id],
        )->assertOk();
    }

    #[Test]
    public function shiftMutationsAreLimitedToCraftsTheUserMayPlan(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $ownCraft->craftShiftPlaner()->attach($user->id);
        $qualification = ShiftQualification::factory()->create();
        $foreignShift = $this->createShiftForUser($user, now(), [
            'project_id' => $project->id,
            'craft_id' => $foreignCraft->id,
        ]);
        ShiftsQualifications::create([
            'shift_id' => $foreignShift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);
        $ownShift = $this->createShiftForUser($user, now(), [
            'project_id' => $project->id,
            'craft_id' => $ownCraft->id,
        ]);
        $payload = [
            'day' => now()->toDateString(),
            'start' => '10:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'room_id' => Room::factory()->create()->id,
            'qualifications' => [],
        ];
        $colleague = User::factory()->create();
        $workerPayload = ['worker_id' => $colleague->id, 'worker_type' => 'user'];

        $this->postJson(route('app.v1.projects.shifts.store', $project), [
            ...$payload,
            'craft_id' => $foreignCraft->id,
        ])->assertForbidden()->assertJsonValidationErrors('craft_id');
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $foreignShift]), [
            ...$payload,
            'craft_id' => $foreignCraft->id,
        ])->assertForbidden();
        $this->postJson(route('app.v1.projects.shifts.workers.store', [$project, $foreignShift]), [
            ...$workerPayload,
            'shift_qualification_id' => $qualification->id,
        ])->assertForbidden();
        $this->deleteJson(
            route('app.v1.projects.shifts.workers.destroy', [$project, $foreignShift]),
            ['worker_id' => $user->id, 'worker_type' => 'user'],
        )->assertForbidden();
        $this->deleteJson(route('app.v1.projects.shifts.destroy', [$project, $foreignShift]))->assertForbidden();

        // Gewerkwechsel: altes UND neues Gewerk müssen planbar sein
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $foreignShift]), [
            ...$payload,
            'craft_id' => $ownCraft->id,
        ])->assertForbidden();
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $ownShift]), [
            ...$payload,
            'craft_id' => $foreignCraft->id,
        ])->assertForbidden();

        $this->assertNotNull($foreignShift->fresh());
        $this->assertSame($foreignCraft->id, $foreignShift->fresh()->craft_id);
        $this->assertSame($ownCraft->id, $ownShift->fresh()->craft_id);
        $this->assertSame(1, $foreignShift->users()->count());
        $this->assertSame(1, $project->shifts()->where('craft_id', $foreignCraft->id)->count());

        // Im eigenen Gewerk ist alles erlaubt
        $this->postJson(route('app.v1.projects.shifts.store', $project), [
            ...$payload,
            'craft_id' => $ownCraft->id,
        ])->assertCreated();
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $ownShift]), [
            ...$payload,
            'craft_id' => $ownCraft->id,
        ])->assertOk();
        $this->deleteJson(route('app.v1.projects.shifts.destroy', [$project, $ownShift]))->assertNoContent();
    }

    #[Test]
    public function shiftUpdateRunsTheWebFollowUpsForCommittedShifts(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $planner = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($planner->id);
        $worker = User::factory()->create();
        // createShiftForUser legt festgeschriebene Schichten an
        $shift = $this->createShiftForUser($worker, now(), ['project_id' => $project->id]);
        $craftPlanner = User::factory()->create();
        $shift->craft->craftShiftPlaner()->attach($craftPlanner->id);
        $payload = [
            'day' => now()->toDateString(),
            'start' => '12:00',
            'end' => '16:00',
            'break_minutes' => 0,
            'craft_id' => $shift->craft_id,
            'qualifications' => [],
        ];

        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shift]), $payload)->assertOk();

        Notification::assertSentTo($worker, ShiftNotification::class);
        Notification::assertSentTo($craftPlanner, ShiftNotification::class);
        $this->assertSame(1, $shift->fresh()->users()->count());

        // Gewerkwechsel entfernt die Besetzung (wie im Web), die Antwort zeigt den neuen Stand
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shift]), [
            ...$payload,
            'craft_id' => Craft::factory()->create()->id,
        ])
            ->assertOk()
            ->assertJsonPath('shift.assigned_count', 0);
        $this->assertSame(0, $shift->fresh()->users()->count());
    }

    #[Test]
    public function shiftCreationIsForbiddenWithoutThePlannerPermission(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);

        Passport::actingAs($user, ['app']);

        $this->postJson(route('app.v1.projects.shifts.store', $project), [
            'day' => now()->toDateString(),
            'start' => '10:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'craft_id' => Craft::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
            'qualifications' => [],
        ])->assertForbidden();
    }

    #[Test]
    public function shiftUpdateIsForbiddenWithoutThePlannerPermission(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);

        Passport::actingAs($user, ['app']);

        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shift]), [
            'day' => now()->toDateString(),
            'start' => '16:00',
            'end' => '22:00',
            'break_minutes' => 45,
            'craft_id' => $shift->craft_id,
            'qualifications' => [],
        ])->assertForbidden();
    }

    #[Test]
    public function projectMembersCanAddAndTickChecklistTasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $checklist = Checklist::factory()->create([
            'project_id' => $project->id,
            'private' => false,
        ]);

        Passport::actingAs($user, ['app']);

        $response = $this->postJson(
            route('app.v1.projects.tasks.store', [$project, $checklist]),
            ['name' => 'Materialliste prüfen', 'deadline' => now()->addDays(3)->toIso8601String()],
        )
            ->assertCreated()
            ->assertJsonPath('task.name', 'Materialliste prüfen')
            ->assertJsonPath('task.done', false);

        $taskId = $response->json('task.id');
        $this->patchJson(route('app.v1.projects.tasks.toggle-done', [$project, $taskId]))
            ->assertOk()
            ->assertJsonPath('task.done', true);
    }

    #[Test]
    public function nonMembersCannotAddChecklistTasks(): void
    {
        $project = Project::factory()->create();
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'private' => false]);

        // Global view permission, but no project/checklist involvement.
        $this->actingAsApiUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->postJson(
            route('app.v1.projects.tasks.store', [$project, $checklist]),
            ['name' => 'X'],
        )->assertForbidden();
    }

    #[Test]
    public function shiftPlannersCanAssignAndRemoveWorkers(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);
        $colleague = User::factory()->create();
        $qualification = \Artwork\Modules\Shift\Models\ShiftQualification::factory()->create();
        ShiftsQualifications::create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);

        $this->postJson(route('app.v1.projects.shifts.workers.store', [$project, $shift]), [
            'worker_id' => $colleague->id,
            'worker_type' => 'user',
            'shift_qualification_id' => $qualification->id,
        ])
            ->assertCreated()
            ->assertJsonPath('shift.assigned_count', 2);

        $this->deleteJson(route('app.v1.projects.shifts.workers.destroy', [$project, $shift]), [
            'worker_id' => $colleague->id,
            'worker_type' => 'user',
        ])
            ->assertOk()
            ->assertJsonPath('shift.assigned_count', 1);
    }

    #[Test]
    public function assigningToAFullFunctionRequiresExplicitOverbookingConfirmation(): void
    {
        app(ShiftSettings::class)->allow_shift_overbooking = true;
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        // One slot, already filled by the shift's own worker.
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);
        $qualification = $shift->users->first()->pivot->shift_qualification_id;
        ShiftsQualifications::create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification,
            'value' => 1,
        ]);
        $colleague = User::factory()->create();
        $payload = [
            'worker_id' => $colleague->id,
            'worker_type' => 'user',
            'shift_qualification_id' => $qualification,
        ];

        // Unconfirmed → 409, and nothing is booked (no silent requirement bump).
        $this->postJson(route('app.v1.projects.shifts.workers.store', [$project, $shift]), $payload)
            ->assertStatus(409);
        $this->assertSame(1, $shift->fresh()->users()->count());

        $this->postJson(
            route('app.v1.projects.shifts.workers.store', [$project, $shift]),
            [...$payload, 'overbook' => true],
        )->assertCreated();

        $this->assertSame(2, $shift->fresh()->users()->count());
    }

    #[Test]
    public function overbookingIsRejectedWhenTheInstanceDisablesIt(): void
    {
        app(ShiftSettings::class)->allow_shift_overbooking = false;
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);
        $qualification = $shift->users->first()->pivot->shift_qualification_id;
        ShiftsQualifications::create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification,
            'value' => 1,
        ]);

        $this->postJson(route('app.v1.projects.shifts.workers.store', [$project, $shift]), [
            'worker_id' => User::factory()->create()->id,
            'worker_type' => 'user',
            'shift_qualification_id' => $qualification,
            'overbook' => true,
        ])->assertStatus(422);

        $this->assertSame(1, $shift->fresh()->users()->count());
    }

    #[Test]
    public function teamMembersCarryRolesAndRightsAndCanBeEdited(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::PROJECT_MANAGEMENT->value);
        $project->users()->attach($user->id);
        $role = ProjectRole::create(['name' => 'Technik']);
        $colleague = User::factory()->create();

        $this->getJson(route('app.v1.projects.show', $project))
            ->assertOk()
            ->assertJsonPath('project.can_edit_team', true);

        $this->postJson(route('app.v1.projects.team.store', $project), [
            'user_id' => $colleague->id,
            'is_manager' => true,
            'roles' => [$role->id, 999999],
        ])->assertCreated();

        $response = $this->patchJson(
            route('app.v1.projects.team.update', [$project, $colleague]),
            ['is_manager' => false, 'can_write' => true, 'roles' => [$role->id]],
        )->assertOk();

        $member = collect($response->json('team'))->firstWhere('id', $colleague->id);
        $this->assertFalse($member['is_manager']);
        $this->assertTrue($member['can_write']);
        // Unknown role ids are dropped, like the web sync does.
        $this->assertSame([['id' => $role->id, 'name' => 'Technik']], $member['roles']);

        $this->deleteJson(route('app.v1.projects.team.destroy', [$project, $colleague]))
            ->assertOk();
        $this->assertFalse($project->users()->whereKey($colleague->id)->exists());
    }

    #[Test]
    public function teamEditingIsForbiddenForPlainMembers(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        // New team members get write access by default; a plain member is one without it.
        $project->users()->attach($user->id, ['can_write' => false]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.show', $project))
            ->assertOk()
            ->assertJsonPath('project.can_edit_team', false);

        $this->postJson(route('app.v1.projects.team.store', $project), [
            'user_id' => User::factory()->create()->id,
        ])->assertForbidden();
    }

    #[Test]
    public function assignableWorkersRequireThePlannerPermission(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $shift = $this->createShiftForUser($user, now(), ['project_id' => $project->id]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.shifts.workers', [$project, $shift]))
            ->assertForbidden();
    }

    #[Test]
    public function projectMembersCanAddComments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);

        Passport::actingAs($user, ['app']);

        $this->postJson(route('app.v1.projects.comments.store', $project), [
            'text' => 'Umbau läuft nach Plan.',
        ])
            ->assertCreated()
            ->assertJsonPath('comment.text', 'Umbau läuft nach Plan.')
            ->assertJsonPath('comment.author', $user->full_name);

        $this->assertSame(1, $project->comments()->count());
    }

    #[Test]
    public function documentsComponentServesSignedDownloadUrls(): void
    {
        Storage::fake();
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        [$tab] = $this->createTabWithComponent('ProjectDocumentsComponent');
        Storage::put('project_files/testfile.pdf', 'pdf-content');
        $file = ProjectFile::create([
            'project_id' => $project->id,
            'name' => 'Bühnenplan.pdf',
            'basename' => 'testfile.pdf',
        ]);

        Passport::actingAs($user, ['app']);

        $response = $this->getJson(route('app.v1.projects.tab', [$project, $tab]))->assertOk();
        $response->assertJsonPath('components.0.value.files.0.name', 'Bühnenplan.pdf');

        // The signed URL must stream the file without any auth token.
        $url = $response->json('components.0.value.files.0.url');
        $this->get($url)->assertOk();
        $this->get(route('app.v1.files.download', $file))->assertForbidden();
    }

    #[Test]
    public function documentsComponentListsBudgetDocumentsOnlyLikeTheWeb(): void
    {
        Storage::fake();
        $project = Project::factory()->create();
        [$tab] = $this->createTabWithComponent('ProjectAllDocumentsComponent');
        $sharedBudgetUser = User::factory()->create();
        $project->users()->attach($sharedBudgetUser->id, ['access_budget' => true]);
        $sharedWithoutBudgetRights = User::factory()->create();
        $project->users()->attach($sharedWithoutBudgetRights->id);
        $budgetFile = $this->createProjectFile($project, 'Budget.pdf', ['is_budget_document' => true]);
        $budgetFile->accessingUsers()->attach([$sharedBudgetUser->id, $sharedWithoutBudgetRights->id]);
        $this->createProjectFile($project, 'Plan.pdf');

        $listedNames = function (User $user) use ($project, $tab): array {
            Passport::actingAs($user, ['app']);

            return collect(
                $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
                    ->assertOk()
                    ->json('components.0.value.files')
            )->pluck('name')->sort()->values()->all();
        };

        $this->assertSame(['Plan.pdf'], $listedNames($sharedWithoutBudgetRights));
        $this->assertSame(['Budget.pdf', 'Plan.pdf'], $listedNames($sharedBudgetUser));
    }

    #[Test]
    public function signedDownloadRechecksTheFileRightsOfTheSignedUser(): void
    {
        Storage::fake();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->users()->attach($member->id);
        $file = $this->createProjectFile($project, 'Plan.pdf');
        $budgetFile = $this->createProjectFile($project, 'Budget.pdf', ['is_budget_document' => true]);
        $hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $hiddenTabFile = $this->createProjectFile($project, 'Hidden.pdf', ['tab_id' => $hiddenTab->id]);

        $this->get($this->signedDownloadUrl($file, $member))->assertOk();
        $this->get($this->signedDownloadUrl($budgetFile, $member))->assertForbidden();
        $this->get($this->signedDownloadUrl($hiddenTabFile, $member))->assertForbidden();

        // Ohne Nutzer in der Signatur oder mit nachträglich getauschtem Nutzer kein Download
        $this->get(URL::temporarySignedRoute('app.v1.files.download', now()->addMinutes(5), [
            'projectFile' => $file->id,
        ]))->assertForbidden();
        $admin = $this->adminUser();
        $this->get(str_replace(
            'user=' . $member->id,
            'user=' . $admin->id,
            $this->signedDownloadUrl($file, $member)
        ))->assertForbidden();

        // Rechte werden beim Download geprüft, nicht beim Ausstellen des Links
        $url = $this->signedDownloadUrl($file, $member);
        $project->users()->detach($member->id);
        $this->get($url)->assertForbidden();

        $this->get($this->signedDownloadUrl($budgetFile, $admin))->assertOk();
    }

    #[Test]
    public function signedDownloadOfAMissingStoredFileIsNotFound(): void
    {
        Storage::fake();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->users()->attach($member->id);
        $file = $this->createProjectFile($project, 'Plan.pdf');
        Storage::delete($file->storagePath());

        $this->get($this->signedDownloadUrl($file, $member))->assertNotFound();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createProjectFile(Project $project, string $name, array $attributes = []): ProjectFile
    {
        $file = ProjectFile::query()->forceCreate(array_merge([
            'project_id' => $project->id,
            'name' => $name,
            'basename' => uniqid() . $name,
        ], $attributes));
        Storage::put($file->storagePath(), 'content');

        return $file;
    }

    private function signedDownloadUrl(ProjectFile $file, User $user): string
    {
        return URL::temporarySignedRoute('app.v1.files.download', now()->addMinutes(5), [
            'projectFile' => $file->id,
            'user' => $user->id,
        ]);
    }

    #[Test]
    public function projectEndpointsRejectTokensWithoutTheAppScope(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson(route('app.v1.projects'))->assertForbidden();
    }
}
