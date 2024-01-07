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
