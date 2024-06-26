<?php

namespace Tests\Feature\Event;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventComment\Models\EventComment;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\FreelancerVacation\Models\FreelancerVacation;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Notifications\RoomNotification;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Inertia\Testing\AssertableInertia;
use function Pest\Faker\fake;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {

    $this->auth_user = User::factory()->create();
    $this->auth_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($this->auth_user);
    setupCalendar($this->auth_user);
});

test('views events', function () {
    $today = today();

    $calendarFilter = $this->auth_user->getCalendarFilter();
    $calendarFilter->end_date = $today;
    $calendarFilter->save();

    $this->get(route('events'));
    $now = now();
    $currentEventCount = Event::startAndEndTimeOverlap($now, $today->endOfDay())->count();

    Event::factory()->create([
        'start_time' => $now,
        'end_time' => $today->endOfDay(),
    ]);

    $response = $this->get(route('events'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Events/EventManagement')
        ->has('events.events', (1 + $currentEventCount)));
});

test('view shiftplan', function () {
    $response = $this->get(route('shifts.plan'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Shifts/ShiftPlan'));
});

test('view shiftplan with freelancer', function (Freelancer $freelancer) {

    $response = $this->get(route('shifts.plan'));

    $freelancers = Freelancer::where('can_work_shifts', true);
    $count = $freelancers->count();

    if ($freelancer->onVacation) {
        $response->assertInertia(fn(AssertableInertia $page) => $page
            ->component('Shifts/ShiftPlan')
            ->has('freelancersForShifts', $count));
    } else {
        $idCount = $count;
        $count--;
        $response->assertInertia(fn(AssertableInertia $page) => $page
            ->component('Shifts/ShiftPlan')
            ->has('freelancersForShifts', $idCount)
            ->where('freelancersForShifts.' . $count . '.freelancer.id', $freelancer->id));
    }
})->with([
    'freelancer can work' => fn() => Freelancer::factory()->create([
        'can_work_shifts' => true,
    ]),
    'freelancer on vacation' => function (): Freelancer {
        $freelancer = Freelancer::factory()->create([
            'can_work_shifts' => true,
        ]);
        $freelancer->onVacation = true;
        FreelancerVacation::factory()->create([
            'freelancer_id' => $freelancer->id,
            'from' => today(),
            'until' => today()->addDay()
        ]);
        return $freelancer;
    }
]);

test('views dashboard', function () {
    $response = $this->get(route('dashboard'));
    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Dashboard'));
});

test('views dashboard with tasks', function (Task $task) {
    $response = $this->get(route('dashboard'));
    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Dashboard')
        ->has('tasks', 2)
        ->where('tasks.0.id', $task->id)
    );
})->with([
    'deadline first' => function (): Task {
        Task::factory()->create([
            'deadline' => null,
            'done' => false,
            'checklist_id' => Checklist::factory()->create([
                'user_id' => $this->auth_user->id,
            ])->id
        ]);
        return Task::factory()->create([
            'done' => false,
            'deadline' => today()->subDay(),
            'checklist_id' => Checklist::factory()->create([
                'user_id' => $this->auth_user->id,
            ])->id
        ]);
    },
    'only done tasks' => function (): Task {
        Task::factory()->create([
            'deadline' => today()->addDay(),
            'done' => true,
            'checklist_id' => Checklist::factory()->create([
                'user_id' => $this->auth_user->id,
            ])->id
        ]);
        Task::factory()->create([
            'deadline' => today()->addDay(),
            'done' => false,
            'checklist_id' => Checklist::factory()->create([
                'user_id' => $this->auth_user->id,
            ])->id
        ]);
        return Task::factory()->create([
            'done' => false,
            'deadline' => today()->subDay(),
            'checklist_id' => Checklist::factory()->create([
                'user_id' => $this->auth_user->id,
            ])->id
        ]);
    }
]);

test('event requests', function (\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection $events) {

    $response = $this->get(route('events.requests'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Events/EventRequestsManagement')
        ->has('event_requests', $events->count()));
})->with([
    'one event' => fn() => Event::factory(1)->create(['occupancy_option' => true]),
    'five events' => fn() => Event::factory(5)->create(['occupancy_option' => true]),
    'no events' => fn() => collect(),
    'events exist but without oc' => function () {
        Event::factory(5)->create(['occupancy_option' => false]);
        return collect();
    }
]);

test('Store event', function () {

    EventFacade::fake();
    $title = fake()->company();

    $data = getEventData();
    $data['title'] = $title;

    $this->post(route('events.store'), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $title]);
});

test('Store event series', function (int $frequency) {
    EventFacade::fake();
    $title = fake()->company();

    $data = getEventData();
    $data['title'] = $title;
    $data['seriesFrequency'] = $frequency;
    $data['user_id'] = $this->auth_user->id;

    $this->post(route('events.store'), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $title]);
    expect(Event::query()->orderBy('id', 'desc')->get()->first()->series->frequency_id)->toBe($frequency);

})->with([
    'day' => 1,
    'week' => 2,
    'weeks' => 3,
    'month' => 4,
]);

test('update events without notification', function () {
    EventFacade::fake();
    NotificationFacade::fake();
    $event = Event::factory()->create();

    $data = getEventData();
    $data['noNotifications'] = true;

    assertDatabaseHas((new Event())->getTable(), ['name' => $event->name]);

    $this->put(route('events.update', $event->id), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $data['title']]);
    NotificationFacade::assertNothingSent();
});

test('update events with admin comment notification', function () {
    EventFacade::fake();
    NotificationFacade::fake();

    $event = Event::factory()->create();

    $data = getEventData();
    $data['adminComment'] = 'test';

    assertDatabaseHas((new Event())->getTable(), ['name' => $event->name]);

    $this->put(route('events.update', $event->id), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $data['title']]);

    NotificationFacade::assertSentTo($event->creator, RoomRequestNotification::class);
});

test('update events with room change notification', function () {
    EventFacade::fake();
    NotificationFacade::fake();

    $event = Event::factory()->create();

    $data = getEventData();
    $data['roomChange'] = true;

    assertDatabaseHas((new Event())->getTable(), ['name' => $event->name]);

    $this->put(route('events.update', $event->id), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $data['title']]);

    NotificationFacade::assertSentTo($event->creator, RoomNotification::class);
});

test('answer on event for single user', function () {
    EventFacade::fake();
    NotificationFacade::fake();

    $event = Event::factory()->create();

    assertDatabaseMissing((new EventComment())->getTable(), ['comment' => 'test']);

    $this->post(route('event.answer', $event->id), [
        'comment' => 'test',
    ]);

    assertDatabaseHas((new EventComment())->getTable(), ['comment' => 'test']);

    NotificationFacade::assertSentTo($event->room->user, RoomRequestNotification::class);
});

test('answer on event for admins', function () {
    EventFacade::fake();
    NotificationFacade::fake();

    $event = Event::factory()->create();

    $admins = User::factory(4)->create();
    $data = [];
    foreach ($admins as $admin) {
        $data[$admin->id] = ['is_admin' => true];
    }

    $event->room->users()->sync($data);
    assertDatabaseMissing((new EventComment())->getTable(), ['comment' => 'test']);

    $this->post(route('event.answer', $event->id), [
        'comment' => 'test',
    ]);

    assertDatabaseHas((new EventComment())->getTable(), ['comment' => 'test']);

    foreach ($admins as $admin) {
        NotificationFacade::assertSentTo($admin, RoomRequestNotification::class);
    }
    NotificationFacade::assertNothingSentTo($event->room->user);
});

test('decline event without managers', function () {

    EventFacade::fake();
    NotificationFacade::fake();

    $event = Event::factory()->create();

    $this->put(route('events.decline', $event->id), []);

    NotificationFacade::assertSentTo($event->creator, RoomRequestNotification::class);
});

test('decline event with managers', function () {
    EventFacade::fake();
    NotificationFacade::fake();

    $managers = User::factory(4)->create();
    $data = [];
    foreach ($managers as $manager) {
        $data[$manager->id] = ['is_manager' => true];
    }
    $event = Event::factory()->create();

    $event->project->managerUsers()->sync($data);
    $this->put(route('events.decline', $event->id), []);

    foreach ($managers as $manager) {
        NotificationFacade::assertSentTo($manager, RoomRequestNotification::class);
    }
});

//
//test('event index', function () {
//    $event = Event::factory(2)->create([
//        'start_time' => today(),
//        'end_time' => today()->addDay(),
//    ]);
//    $filter = new \stdClass();
//    $filter->roomIds = null;
//    $filter->roomAttributeIds = null;
//    $filter->areaIds = null;
//    $filter->roomCategoryIds = null;
//    $filter->eventTypeIds = null;
//    $filter->isLoud = null;
//    $filter->isNotLoud = null;
//    $filter->showAdjoiningRooms = null;
//    $filter->hasAudience = null;
//    $filter->hasNoAudience = null;
//    $filter->adjoiningNoAudience = null;
//    $filter->adjoiningNotLoud = null;
//    $data = [
//        'start' => today()->format('Y-m-d'),
//        'end' => today()->addDay()->format('Y-m-d'),
//        'calendarFilters' => json_encode($filter),
//    ];
//    $response = $this->get(route('events.index', $data));
//
//    expect($response->getContent())->toBe(1);
//});

test('destroy shifts', function () {
    $event = Event::factory()->create();
    $shift = Shift::factory()->create(['event_id' => $event->id]);
    $timeline = Timeline::factory()->create(['event_id' => $event->id]);

    assertDatabaseHas('shifts', ['id' => $shift->id, 'event_id' => $event->id]);
    assertDatabaseHas('timelines', ['id' => $timeline->id, 'event_id' => $event->id]);

    $this->delete(route('events.shifts.delete', $event->id));

    assertDatabaseMissing('shifts', ['id' => $shift->id, 'event_id' => $event->id]);
    assertDatabaseMissing('timelines', ['id' => $timeline->id, 'event_id' => $event->id]);
});

test('delete event', function () {
    EventFacade::fake();
    NotificationFacade::fake();

    $managers = User::factory(4)->create();
    $data = [];
    foreach ($managers as $manager) {
        $data[$manager->id] = ['is_manager' => true];
    }
    $event = Event::factory()->create();

    $event->project->managerUsers()->sync($data);

    $this->delete(route('events.delete', $event->id));
    assertSoftDeleted('events', ['id' => $event->id]);
    foreach ($managers as $manager) {
        NotificationFacade::assertSentTo($manager, RoomRequestNotification::class);
    }
});

test('force delete', function (Event $event) {
    EventFacade::fake();
    NotificationFacade::fake();
    /** @var \Illuminate\Testing\TestResponse $response */
    $response = $this->delete(route('events.force', $event->id));
    if ($event->deleted_at !== null) {
        $response->assertRedirect(route('events.trashed'));
        EventFacade::assertDispatched(\Artwork\Modules\Event\Events\OccupancyUpdated::class);
        assertDatabaseMissing((new Event())->getTable(), ['id' => $event->id]);
    } else {
        $response->assertNotFound();
        assertDatabaseHas((new Event())->getTable(), ['id' => $event->id]);
    }

})->with([
    'event not soft deleted' => function () {
        return Event::factory()->create();
    },
    'event soft deleted' => function () {
        $event = Event::factory()->create();
        $event->delete();
        return $event;
    }
]);

test('restore event', function (Event $event) {
    EventFacade::fake();
    NotificationFacade::fake();
    /** @var \Illuminate\Testing\TestResponse $response */
    $response = $this->patch(route('events.restore', $event->id));
    if ($event->deleted_at !== null) {
        $response->assertRedirect(route('events.trashed'));
        EventFacade::assertDispatched(\Artwork\Modules\Event\Events\OccupancyUpdated::class);
        assertDatabaseHas((new Event())->getTable(), ['id' => $event->id, 'deleted_at' => null]);
    } else {
        $response->assertNotFound();
        assertDatabaseHas((new Event())->getTable(), ['id' => $event->id, 'deleted_at' => $event->deleted_at]);
    }

})->with([
    'event not soft deleted' => function () {
        return Event::factory()->create();
    },
    'event soft deleted' => function () {
        $event = Event::factory()->create();
        $event->delete();
        return $event;
    }
]);

test('multi delete', function () {
    $events = Event::factory(5)->create();
    $this->post(route('multi-edit.delete'), ['events' => $events->pluck('id')->toArray()]);
    foreach ($events as $event) {
        assertSoftDeleted($event);
    }
});

function setupCalendar(User $user): array
{
    $today = today();
    $tomorrow = today()->addDay();
    $userCalendarFilter = UserCalendarFilter::factory()->create([
        'user_id' => $user->id,
        'start_date' => $today,
        'end_date' => $tomorrow,
    ]);

    $userShiftCalendarFilter = UserShiftCalendarFilter::factory()->create([
        'user_id' => $user->id,
        'start_date' => $today,
        'end_date' => $tomorrow,
    ]);

    $userCalendarSettings = UserCalendarSettings::factory()->create([
        'user_id' => $user->id,
    ]);

    return [$userCalendarFilter, $userShiftCalendarFilter, $userCalendarSettings];
}

function getEventData(): array
{
    return [
        'start' => now()->format('Y-m-d'),
        'end' => now()->addYear()->format('Y-m-d'),
        'roomId' => Room::factory()->create()->id,
        'eventName' => null,
        'description' => fake()->text(),
        'title' => fake()->company(),
        'audience' => fake()->boolean(),
        'isLoud' => fake()->boolean(),
        'projectId' => Project::factory()->create()->id,
        'eventTypeId' => 1,
        'projectIdMandatory' => false,
        'eventNameMandatory' => false,
        'creatingProject' => false,
        'isOption' => false,
        'is_series' => true,
        'seriesEndDate' => now()->addMonths(2),
        'allDay' => true,
    ];
}
