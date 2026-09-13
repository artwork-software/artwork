<?php

namespace Tests\Feature\Modules\WorkTime;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Zeitanpassungs-Anfragen (eigene + erhaltene): Statusfilter, Zeitraum, Paginierung 25, Rechte.
 */
final class WorkTimeChangeRequestListTest extends FeatureTestCase
{
    private function request(User $user, Craft $craft, array $overrides = []): WorkTimeChangeRequest
    {
        $request = WorkTimeChangeRequest::create(array_merge([
            'user_id' => $user->id,
            'craft_id' => $craft->id,
            'shift_id' => null,
            'request_start_time' => '09:00',
            'request_end_time' => '17:00',
            'status' => 'pending',
        ], $overrides));

        if (isset($overrides['created_at'])) {
            WorkTimeChangeRequest::query()->whereKey($request->id)->update(['created_at' => $overrides['created_at']]);
        }

        return $request->fresh();
    }

    #[Test]
    public function my_requests_are_paginated_and_filtered_by_status_and_period(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $craft = Craft::factory()->create();

        for ($i = 0; $i < 28; $i++) {
            $this->request($user, $craft, ['created_at' => Carbon::parse('2026-03-01')->addDays($i)->toDateTimeString()]);
        }
        $approved = $this->request($user, $craft, ['status' => 'approved', 'created_at' => '2026-04-10 10:00:00']);
        $this->request($user, $craft, ['status' => 'rejected', 'created_at' => '2026-04-11 10:00:00']);
        $this->request(User::factory()->create(), $craft); // fremde Anfrage bleibt unsichtbar

        // Default: alle Status, 25 je Seite, neueste zuerst
        $this->get(route('work-time-request.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('WorkTime/MyRequests')
                ->has('requests.data', 25)
                ->where('requests.total', 30)
                ->where('requests.per_page', 25)
                ->where('filters.status', 'all'));

        $this->get(route('work-time-request.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('requests.data', 5));

        // Status
        $this->get(route('work-time-request.index', ['status' => 'approved']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.id', $approved->id)
                ->where('filters.status', 'approved'));

        // Zeitraum (Anfragedatum)
        $this->get(route('work-time-request.index', ['date_from' => '2026-04-01', 'date_to' => '2026-04-30']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 2)
                ->where('filters.date_from', '2026-04-01'));
    }

    #[Test]
    public function received_requests_default_to_pending_and_allow_all_statuses(): void
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $worker = User::factory()->create();

        for ($i = 0; $i < 26; $i++) {
            $this->request($worker, $craft, ['created_at' => Carbon::parse('2026-03-01')->addDays($i)->toDateTimeString()]);
        }
        $this->request($worker, $craft, ['status' => 'approved', 'approved_by' => $planner->id]);

        $this->get(route('work-time-request.received'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('WorkTime/ReceivedRequests')
                ->has('requests.data', 25)
                ->where('requests.total', 26)
                ->where('filters.status', 'pending'));

        $this->get(route('work-time-request.received', ['status' => 'all']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('requests.total', 27));

        $this->get(route('work-time-request.received', ['status' => 'approved']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('requests.total', 1));
    }

    #[Test]
    public function received_requests_require_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('work-time-request.received'))->assertForbidden();
    }
}
