<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftPlanRequestControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_review(): void
    {
        $this->get(route('shifts.approvals.review'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_review_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.review'))->assertOk();
    }

    #[Test]
    public function admin_can_view_my_requests_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.requests'))->assertOk();
    }

    #[Test]
    public function admin_can_view_changes(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.changes'))->assertOk();
    }

    #[Test]
    public function admin_can_view_past_requests(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $response = $this->get(route('shifts.approvals.past-requests', [
            'craft' => $craft->id,
            'status' => 'approved',
            'offset' => 0,
        ]));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_create_shift_plan_request(): void
    {
        $this->post(route('commit-shift-workflow-request.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_show_shift_plan_request(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
        ]);

        $response = $this->get(route('shift-plan-requests.show', $request));

        $response->assertOk();
    }

    #[Test]
    public function show_contains_navigation_between_requests_of_same_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw28 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 28, 'year' => 2026]);
        $kw29 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 29, 'year' => 2026]);
        $kw30 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 30, 'year' => 2026]);
        // Anfrage eines anderen Gewerks darf in der Navigation nicht auftauchen
        ShiftPlanRequest::factory()->create(['week_number' => 29, 'year' => 2026]);

        $this->get(route('shift-plan-requests.show', $kw29))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftPlanRequests/Show')
                ->where('navigation.previous.id', $kw28->id)
                ->where('navigation.next.id', $kw30->id));
    }

    #[Test]
    public function navigation_is_empty_at_the_edges(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw28 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 28, 'year' => 2026]);
        $kw29 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 29, 'year' => 2026]);

        $this->get(route('shift-plan-requests.show', $kw28))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.previous', null)
                ->where('navigation.next.id', $kw29->id));

        $this->get(route('shift-plan-requests.show', $kw29))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.previous.id', $kw28->id)
                ->where('navigation.next', null));
    }

    #[Test]
    public function show_shifts_include_room_name(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $room = Room::factory()->create(['name' => 'Halle K1']);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 28,
            'year' => 2026,
        ]);
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'start_date' => '2026-07-07',
            'end_date' => '2026-07-07',
        ]);
        $request->requestedShifts()->attach($shift->id, ['snapshot' => json_encode([])]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('shifts.0.room_name', 'Halle K1'));
    }

    // --- Prüfansicht: Schichten aus anderen Gewerken (Wochenlast der Person) --------------------

    /**
     * KW 28/2026 = 06.07.–12.07.2026. Anfrage des Gewerks "Licht", eine Person ist dort und in "Ton"
     * eingeteilt.
     *
     * @return array{0: ShiftPlanRequest, 1: Craft, 2: Shift}
     */
    private function requestWithAssignedWorkerShift(User|Freelancer $worker, string $relation): array
    {
        $craft = Craft::factory()->create(['name' => 'Licht', 'abbreviation' => 'LI']);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 28,
            'year' => 2026,
        ]);
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-07-07',
            'end_date' => '2026-07-07',
            'start' => '08:00',
            'end' => '16:00',
        ]);
        $this->assign($shift, $relation, $worker->id);
        $request->requestedShifts()->attach($shift->id, ['snapshot' => json_encode([])]);

        return [$request, $craft, $shift];
    }

    /**
     * shift_workers.shift_qualification_id hat keinen Default, deshalb immer mit Qualifikation zuordnen.
     *
     * @param array<int,int>|int $ids
     */
    private function assign(Shift $shift, string $relation, array|int $ids): void
    {
        $qualificationId = ShiftQualification::factory()->create()->id;
        foreach ((array) $ids as $id) {
            $shift->{$relation}()->attach($id, ['shift_qualification_id' => $qualificationId]);
        }
    }

    private function foreignShift(Craft $craft, string $date, array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'craft_id' => $craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '18:00',
            'end' => '23:00',
            'break_minutes' => 30,
        ], $attributes));
    }

    #[Test]
    public function show_contains_shifts_of_other_crafts_for_assigned_people(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create();
        [$request] = $this->requestWithAssignedWorkerShift($worker, 'users');
        $sound = Craft::factory()->create(['name' => 'Ton', 'abbreviation' => 'TO']);
        $room = Room::factory()->create(['name' => 'Studio']);
        $foreign = $this->foreignShift($sound, '2026-07-08', ['room_id' => $room->id, 'is_committed' => true]);
        $this->assign($foreign, 'users', $worker->id);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('foreignCraftShifts', 1)
                ->where('foreignCraftShifts.0.id', $foreign->id)
                ->where('foreignCraftShifts.0.date', '2026-07-08')
                ->where('foreignCraftShifts.0.start', '18:00')
                ->where('foreignCraftShifts.0.end', '23:00')
                ->where('foreignCraftShifts.0.break_minutes', 30)
                ->where('foreignCraftShifts.0.is_committed', true)
                ->where('foreignCraftShifts.0.room_name', 'Studio')
                ->where('foreignCraftShifts.0.craft.abbreviation', 'TO')
                ->where('foreignCraftShifts.0.craft.name', 'Ton')
                ->where('foreignCraftShifts.0.workers.0.type', 'user')
                ->where('foreignCraftShifts.0.workers.0.id', $worker->id)
                ->where('foreignCraftShifts.0.workers.0.shift_qualification_id', fn ($id) => is_int($id))
                // Die Schichten der Anfrage selbst bleiben unverändert
                ->has('shifts', 1));
    }

    #[Test]
    public function foreign_craft_shifts_only_cover_people_of_the_request_and_its_week(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create();
        $otherPerson = User::factory()->create();
        [$request, $craft] = $this->requestWithAssignedWorkerShift($worker, 'users');
        $sound = Craft::factory()->create();

        // Person ohne Schicht in der Anfrage: ihre fremden Schichten gehören nicht in die Ansicht
        $this->assign($this->foreignShift($sound, '2026-07-08'), 'users', $otherPerson->id);
        // Gleiche Person, aber andere KW
        $this->assign($this->foreignShift($sound, '2026-07-14'), 'users', $worker->id);
        // Gleiches Gewerk: ist keine fremde Schicht
        $this->assign($this->foreignShift($craft, '2026-07-09'), 'users', $worker->id);
        // Zuordnung wieder entfernt (soft-gelöscht)
        $removed = $this->foreignShift($sound, '2026-07-10');
        $this->assign($removed, 'users', $worker->id);
        $removed->users()->updateExistingPivot($worker->id, ['deleted_at' => now()]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('foreignCraftShifts', 0));
    }

    #[Test]
    public function foreign_craft_shifts_only_list_the_people_of_the_request(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create();
        $otherPerson = User::factory()->create();
        [$request] = $this->requestWithAssignedWorkerShift($worker, 'users');
        $foreign = $this->foreignShift(Craft::factory()->create(), '2026-07-08');
        $this->assign($foreign, 'users', [$worker->id, $otherPerson->id]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('foreignCraftShifts', 1)
                ->has('foreignCraftShifts.0.workers', 1)
                ->where('foreignCraftShifts.0.workers.0.id', $worker->id));
    }

    #[Test]
    public function foreign_craft_shifts_include_freelancers_and_the_requesters_view(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();
        [$request] = $this->requestWithAssignedWorkerShift($freelancer, 'freelancer');
        $foreign = $this->foreignShift(Craft::factory()->create(['abbreviation' => 'AZ']), '2026-07-12');
        $this->assign($foreign, 'freelancer', $freelancer->id);

        $this->get(route('shift-plan-requests.my.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('foreignCraftShifts', 1)
                ->where('foreignCraftShifts.0.craft.abbreviation', 'AZ')
                ->where('foreignCraftShifts.0.workers.0.type', 'freelancer')
                ->where('foreignCraftShifts.0.workers.0.id', $freelancer->id));
    }

    #[Test]
    public function show_without_assigned_people_has_no_foreign_craft_shifts(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 28,
            'year' => 2026,
        ]);
        $foreign = $this->foreignShift(Craft::factory()->create(), '2026-07-08');
        $this->assign($foreign, 'users', User::factory()->create()->id);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('foreignCraftShifts', []));
    }

    #[Test]
    public function navigation_orders_across_year_boundary(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw52 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 52, 'year' => 2026]);
        $kw1 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 1, 'year' => 2027]);

        $this->get(route('shift-plan-requests.show', $kw52))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.next.id', $kw1->id));
    }

    // --- Prüfansicht: Verstoß-Payload für das ViolationEditModal ------------------------------

    private function violationInCurrentWeekFor(\Artwork\Modules\User\Models\User $user): array
    {
        $date = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(2);
        $rule = \Artwork\Modules\Shift\Models\ShiftRule::factory()->create([
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'default_compensation_days' => 1.0,
            'default_compensation_deadline_days' => 30,
        ]);
        $violation = \Artwork\Modules\Shift\Models\ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'shift_id' => null,
            'user_id' => $user->id,
            'violation_date' => $date->toDateString(),
            'violation_data' => ['planned_hours' => 9.5, 'max_allowed' => 8.0],
            'severity' => 'warning',
            'status' => 'active',
        ]);

        return [$date, $rule, $violation];
    }

    #[Test]
    public function show_provides_the_full_violation_payload_for_the_edit_modal(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $worker = \Artwork\Modules\User\Models\User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($worker->id);
        [$date, $rule] = $this->violationInCurrentWeekFor($worker);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => $date->isoWeek(),
            'year' => $date->isoWeekYear(),
        ]);

        $prefix = "shiftRuleViolations.{$worker->id}.{$date->toDateString()}.0";

        $this->get(route('shift-plan-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftPlanRequests/Show')
                ->where('canEditViolations', true)
                ->where("{$prefix}.violation_data.planned_hours", 9.5)
                ->where("{$prefix}.severity", 'warning')
                ->where("{$prefix}.status", 'active')
                ->has("{$prefix}.compensation_days")
                ->has("{$prefix}.compensation_deadline")
                ->has("{$prefix}.compensation_reason")
                ->has("{$prefix}.resolved_by")
                ->has("{$prefix}.resolved_at")
                ->has("{$prefix}.title")
                ->where("{$prefix}.shift_rule.trigger_type", 'maxWorkingHoursOnDay')
                ->where("{$prefix}.shift_rule.name", $rule->name)
                ->has("{$prefix}.shift_rule.description")
                ->has("{$prefix}.shift_rule.warning_color")
                ->has("{$prefix}.shift_rule.default_compensation_days")
                ->where("{$prefix}.shift_rule.default_compensation_deadline_days", 30));
    }

    #[Test]
    public function reviewers_without_rule_edit_permission_cannot_edit_violations_from_the_review(): void
    {
        // Workflow-Nutzer:in ohne Schichteinstellungs-Recht: sieht die Prüfansicht, aber nur Tooltip
        $reviewer = $this->actingAsUserWith(\Artwork\Modules\Permission\Enums\PermissionEnum::SHIFT_PLANNER->value);
        \Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser::create(['user_id' => $reviewer->id]);
        $craft = Craft::factory()->create();
        $worker = \Artwork\Modules\User\Models\User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($worker->id);
        [$date, , $violation] = $this->violationInCurrentWeekFor($worker);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => $date->isoWeek(),
            'year' => $date->isoWeekYear(),
        ]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canEditViolations', false));

        // Ohne Planungsrecht: Bearbeiten-Endpunkt liefert 403
        $this->actingAsUserWith([]);
        $this->put(route('shift-rule-violations.process', ['violation' => $violation->id]), [
            'compensation_days' => 1.0,
            'compensation_deadline' => \Carbon\Carbon::now()->addDays(20)->toDateString(),
        ])->assertForbidden();
    }
}
