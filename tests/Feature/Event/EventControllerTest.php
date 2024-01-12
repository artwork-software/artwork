<?php

use App\Models\User;
use App\Models\Event;
use App\Models\UserCalendarFilter;
use App\Models\UserShiftCalendarFilter;
use App\Models\UserCalendarSettings;
use Inertia\Testing\AssertableInertia;
use App\Models\Freelancer;
use App\Models\FreelancerVacation;
use App\Models\Task;
use Artwork\Modules\Checklist\Models\Checklist;
use Illuminate\Support\Facades\Event as EventFacade;

use function Pest\Faker\faker;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {

    $this->auth_user = User::factory()->create();
    $this->auth_user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
    $this->actingAs($this->auth_user);
    setupCalendar($this->auth_user);

});

test('views events', function () {
    $today = today();
    $tomorrow = today()->addDay();

    setupCalendar($this->auth_user);

    $response = $this->get(route('events'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Events/EventManagement')
        ->has('events.events', 17));

    Event::factory()->create([
        'start_time' => $today,
        'end_time' => $tomorrow,
    ]);

    $response = $this->get(route('events'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Events/EventManagement')
        ->has('events.events', 18));
});

test('view shiftplan', function () {
    $response = $this->get(route('shifts.plan'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Shifts/ShiftPlan'));
});

test('view shiftplan with freelancer', function (Freelancer $freelancer) {

    $response = $this->get(route('shifts.plan'));

    $response->assertInertia(fn(AssertableInertia $page) => $page
        ->component('Shifts/ShiftPlan')
        ->where('freelancersForShifts.0.freelancer.id', $freelancer->id));
})->with([
    'freelancer can work' => fn() => Freelancer::factory()->create([
        'can_work_shifts' => true,
    ]),
    'freelancer on vacation' => function (): Freelancer {
        $freelancer = Freelancer::factory()->create([
            'can_work_shifts' => true,
        ]);
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
    $title = faker()->company();

    $data = [
        'start' => now()->format('Y-m-d'),
        'end' => now()->addDay()->format('Y-m-d'),
        'roomId' => \Artwork\Modules\Room\Models\Room::factory()->create()->id,
        'title' => $title,
        'eventName' => null,
        'description' => faker()->text(),
        'audience' => faker()->boolean(),
        'isLoud' => faker()->boolean(),
        'projectId' => \Artwork\Modules\Project\Models\Project::factory()->create()->id,
        'eventTypeId' => 1,
        'projectIdMandatory' => false,
        'eventNameMandatory' => false,
        'creatingProject' => false,
        'user_id' => $this->auth_user->id,
        'isOption' => false,
        'is_series' => false,
        'seriesFrequency' => null,
        'seriesEndDate' => null,
        'allDay' => true,
    ];

    $this->post(route('events.store'), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $title]);
});

test('Store event series', function(int $frequency) {
    EventFacade::fake();
    $title = faker()->company();

    $data = [
        'start' => now()->format('Y-m-d'),
        'end' => now()->addYear()->format('Y-m-d'),
        'roomId' => \Artwork\Modules\Room\Models\Room::factory()->create()->id,
        'title' => $title,
        'eventName' => null,
        'description' => faker()->text(),
        'audience' => faker()->boolean(),
        'isLoud' => faker()->boolean(),
        'projectId' => \Artwork\Modules\Project\Models\Project::factory()->create()->id,
        'eventTypeId' => 1,
        'projectIdMandatory' => false,
        'eventNameMandatory' => false,
        'creatingProject' => false,
        'user_id' => $this->auth_user->id,
        'isOption' => false,
        'is_series' => true,
        'seriesFrequency' => $frequency,
        'seriesEndDate' => now()->addMonths(2),
        'allDay' => true,
    ];

    $this->post(route('events.store'), $data);

    assertDatabaseHas((new Event())->getTable(), ['name' => $title]);
    expect(Event::query()->orderBy('id', 'desc')->get()->first()->series->frequency_id)->toBe($frequency);

})->with([
    'day' => 1,
    'week' => 2,
    'weeks' => 3,
    'month' => 4,
]);

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
