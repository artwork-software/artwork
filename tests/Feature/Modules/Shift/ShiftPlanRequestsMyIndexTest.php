<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * "Meine Freigabe-Anfragen" (shift-plan-requests.my.index): flache Liste, KW absteigend, 25 je Seite,
 * Filter Status/Gewerk/nur eigene, Sichtbarkeit wie zuvor.
 */
final class ShiftPlanRequestsMyIndexTest extends FeatureTestCase
{
    private function request(User $requester, Craft $craft, int $week, int $year = 2026, string $status = 'pending'): ShiftPlanRequest
    {
        return ShiftPlanRequest::create([
            'craft_id' => $craft->id,
            'week_number' => $week,
            'year' => $year,
            'status' => $status,
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function list_is_flat_paginated_and_sorted_by_calendar_week_descending(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        for ($week = 1; $week <= 30; $week++) {
            $this->request($admin, $craft, $week);
        }
        $lastYear = $this->request($admin, $craft, 52, 2025);

        $this->get(route('shift-plan-requests.my.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftPlanRequests/MyIndex')
                ->has('requests.data', 25)
                ->where('requests.total', 31)
                ->where('requests.data.0.week_number', 30)
                ->where('requests.data.24.week_number', 6)
                ->where('filters.status', 'all')
                ->where('isPlanner', true)
                ->has('crafts', 1));

        $this->get(route('shift-plan-requests.my.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 6)
                // KW 52/2025 liegt hinter KW 1/2026 (Jahr vor Woche)
                ->where('requests.data.5.id', $lastYear->id));
    }

    #[Test]
    public function list_filters_by_status_craft_and_only_mine(): void
    {
        $admin = $this->actingAsAdmin();
        $other = User::factory()->create();
        $craftA = Craft::factory()->create();
        $craftB = Craft::factory()->create();

        $mineA = $this->request($admin, $craftA, 10);
        $otherA = $this->request($other, $craftA, 11, 2026, 'approved');
        $mineB = $this->request($admin, $craftB, 12, 2026, 'rejected');
        $this->request($admin, $craftB, 12); // ausstehende Geschwister-Anfrage gleicher KW

        $this->get(route('shift-plan-requests.my.index', ['status' => 'approved']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.id', $otherA->id));

        $this->get(route('shift-plan-requests.my.index', ['craft_id' => $craftA->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 2)
                ->where('filters.craft_id', $craftA->id));

        $this->get(route('shift-plan-requests.my.index', ['only_mine' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 3)
                ->where('filters.only_mine', true));

        // has_pending_sibling: abgelehnte KW 12 hat eine ausstehende Geschwister-Anfrage -> kein Re-Submit
        $this->get(route('shift-plan-requests.my.index', ['status' => 'rejected']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.id', $mineB->id)
                ->where('requests.data.0.has_pending_sibling', true));
        $this->get(route('shift-plan-requests.my.index', ['craft_id' => $craftA->id, 'status' => 'pending']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('requests.data.0.id', $mineA->id)
                ->where('requests.data.0.has_pending_sibling', false));
    }

    #[Test]
    public function non_planners_only_see_their_own_requests(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($me);

        $mine = $this->request($me, $craft, 5);
        $this->request($other, $craft, 6);

        $this->get(route('shift-plan-requests.my.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.id', $mine->id)
                ->where('isPlanner', false));
    }
}
