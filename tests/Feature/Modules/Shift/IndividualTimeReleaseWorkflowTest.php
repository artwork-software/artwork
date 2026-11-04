<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftPlanWorkflowStatusService;
use Artwork\Modules\Shift\Services\ShiftWeekStatusService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Individuelle Zeiten laufen wie Schichten durch die Dienstplan-Freigabe: eine Anfrage nimmt die
 * freien Zeiten der Personen des Gewerks auf, die Genehmigung gibt sie frei, Ablehnung und Rückzug
 * lösen sie wieder. Jede Zeit gehört genau einer Anfrage; Änderungen heben eine Freigabe auf.
 *
 * KW 19/2026 = Mo 04.05. – So 10.05.2026.
 */
final class IndividualTimeReleaseWorkflowTest extends FeatureTestCase
{
    private function craftWithMember(): array
    {
        $craft = Craft::factory()->create();
        // Craft::users() liefert nur Personen, die Schichten übernehmen können
        $member = User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($member->id);

        return [$craft, $member];
    }

    private function individualTimeFor(
        User|Freelancer $owner,
        string $date = '2026-05-06',
        array $attributes = []
    ): IndividualTime {
        return IndividualTime::factory()->create(array_merge([
            'timeable_type' => $owner->getMorphClass(),
            'timeable_id' => $owner->id,
            'title' => 'Berufsschule',
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '08:00',
            'end_time' => '15:00',
        ], $attributes));
    }

    private function submitRequest(Craft $craft): ShiftPlanRequest
    {
        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        return ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('status', 'pending')
            ->latest('id')
            ->firstOrFail();
    }

    #[Test]
    public function request_collects_free_individual_times_of_the_craft_people_in_its_week(): void
    {
        $this->actingAsAdmin();
        [$craft, $member] = $this->craftWithMember();
        $time = $this->individualTimeFor($member);
        $otherWeek = $this->individualTimeFor($member, '2026-05-12');
        $stranger = $this->individualTimeFor(User::factory()->create());

        $request = $this->submitRequest($craft);

        $time->refresh();
        $this->assertTrue($time->in_workflow);
        $this->assertFalse($time->is_committed);
        $this->assertSame($request->id, $time->current_request_id);
        $this->assertSame('requested', $time->workflowStatus());
        $this->assertSame([$time->id], $request->requestedIndividualTimes()->pluck('individual_times.id')->all());

        $this->assertFalse($otherWeek->refresh()->in_workflow);
        $this->assertFalse($stranger->refresh()->in_workflow);
    }

    #[Test]
    public function request_also_collects_times_of_people_assigned_to_shifts_of_the_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $borrowed = Freelancer::factory()->create();
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-05',
            'end_date' => '2026-05-05',
        ]);
        $shift->freelancer()->attach($borrowed->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);
        $time = $this->individualTimeFor($borrowed);

        $request = $this->submitRequest($craft);

        $this->assertSame($request->id, $time->refresh()->current_request_id);
    }

    #[Test]
    public function an_individual_time_belongs_to_the_first_request_only(): void
    {
        $this->actingAsAdmin();
        [$craftA, $trainee] = $this->craftWithMember();
        $craftB = Craft::factory()->create();
        $craftB->users()->attach($trainee->id);
        $time = $this->individualTimeFor($trainee);

        $requestA = $this->submitRequest($craftA);
        $requestB = $this->submitRequest($craftB);

        $this->assertSame($requestA->id, $time->refresh()->current_request_id);
        $this->assertSame(0, $requestB->requestedIndividualTimes()->count());

        // Auch nach der Freigabe nimmt eine weitere Anfrage sie nicht erneut auf
        $this->post(route('shift-plan-requests.accept', $requestA))->assertRedirect();
        $this->delete(route('shift-plan-requests.destroy', $requestB))->assertRedirect();
        $requestB2 = $this->submitRequest($craftB);

        $this->assertSame(0, $requestB2->requestedIndividualTimes()->count());
        $this->assertTrue($time->refresh()->is_committed);
    }

    #[Test]
    public function approving_releases_the_individual_times_of_the_request(): void
    {
        $approver = $this->actingAsAdmin();
        [$craft, $member] = $this->craftWithMember();
        $time = $this->individualTimeFor($member);
        $request = $this->submitRequest($craft);

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        $time->refresh();
        $this->assertTrue($time->is_committed);
        $this->assertFalse($time->in_workflow);
        $this->assertNull($time->current_request_id);
        $this->assertSame($approver->id, $time->committing_user_id);
        $this->assertSame('committed', $time->workflowStatus());
        // Der Verlauf hält die Zugehörigkeit fest
        $this->assertSame(1, $request->requestedIndividualTimes()->count());
    }

    #[Test]
    public function rejecting_frees_the_individual_times_for_the_next_request(): void
    {
        $this->actingAsAdmin();
        [$craft, $member] = $this->craftWithMember();
        $time = $this->individualTimeFor($member);
        $rejected = $this->submitRequest($craft);

        $this->post(route('shift-plan-requests.reject', $rejected), ['global_reason' => 'Zu viel'])
            ->assertRedirect();

        $time->refresh();
        $this->assertFalse($time->in_workflow);
        $this->assertFalse($time->is_committed);
        $this->assertNull($time->workflowStatus());

        $resubmitted = $this->submitRequest($craft);

        $this->assertSame($resubmitted->id, $time->refresh()->current_request_id);
        $this->assertTrue($time->in_workflow);
    }

    #[Test]
    public function withdrawing_releases_the_individual_times(): void
    {
        $this->actingAsAdmin();
        [$craft, $member] = $this->craftWithMember();
        $time = $this->individualTimeFor($member);
        $request = $this->submitRequest($craft);

        $this->delete(route('shift-plan-requests.destroy', $request))->assertRedirect();

        $time->refresh();
        $this->assertFalse($time->in_workflow);
        $this->assertNull($time->current_request_id);
        $this->assertDatabaseMissing('shift_plan_request_individual_times', ['individual_time_id' => $time->id]);
    }

    #[Test]
    public function changing_a_released_individual_time_revokes_the_release(): void
    {
        $owner = User::factory()->create();
        $time = $this->individualTimeFor($owner, attributes: ['is_committed' => true]);

        $time->update(['end_time' => '16:00']);

        $this->assertFalse($time->refresh()->is_committed);
        $this->assertNull($time->workflowStatus());
    }

    #[Test]
    public function changing_a_requested_individual_time_keeps_it_in_the_request(): void
    {
        $this->actingAsAdmin();
        [$craft, $member] = $this->craftWithMember();
        $time = $this->individualTimeFor($member);
        $request = $this->submitRequest($craft);

        $time->refresh()->update(['start_time' => '09:00']);

        $time->refresh();
        $this->assertTrue($time->in_workflow);
        $this->assertSame($request->id, $time->current_request_id);
    }

    #[Test]
    public function release_survives_updates_that_do_not_touch_the_content(): void
    {
        $time = $this->individualTimeFor(User::factory()->create(), attributes: ['is_committed' => true]);

        $time->update(['end_time' => '15:00', 'in_workflow' => false]);

        $this->assertTrue($time->refresh()->is_committed);
    }

    #[Test]
    public function the_kw_tile_turns_green_for_a_week_with_only_released_individual_times(): void
    {
        $member = User::factory()->create();
        $this->individualTimeFor($member, attributes: ['is_committed' => true]);

        $status = app(ShiftPlanWorkflowStatusService::class)
            ->computeForDateRange(Carbon::parse('2026-05-04'), Carbon::parse('2026-05-10'));

        $this->assertSame(
            'committed',
            $status['user'][$member->id][ShiftPlanWorkflowStatusService::INDIVIDUAL_TIMES_KEY][19]
        );
    }

    #[Test]
    public function individual_times_count_into_the_kw_tile_of_every_craft_row(): void
    {
        $member = User::factory()->create();
        $craftCommitted = Craft::factory()->create();
        $craftOpen = Craft::factory()->create();
        $qualificationId = ShiftQualification::factory()->create()->id;

        $committedShift = Shift::factory()->create([
            'craft_id' => $craftCommitted->id,
            'start_date' => '2026-05-05',
            'end_date' => '2026-05-05',
        ]);
        $committedShift->users()->attach($member->id, ['shift_qualification_id' => $qualificationId]);
        // Erst nach dem Zuweisen festschreiben (ohne Model-Events), sonst zählt das Zuweisen als
        // nachträgliche Änderung und die KW wäre "attention"
        Shift::query()->whereKey($committedShift->id)->update(['is_committed' => true]);
        $openShift = Shift::factory()->create([
            'craft_id' => $craftOpen->id,
            'start_date' => '2026-05-07',
            'end_date' => '2026-05-07',
        ]);
        $openShift->users()->attach($member->id, ['shift_qualification_id' => $qualificationId]);

        $time = $this->individualTimeFor($member, attributes: ['in_workflow' => true]);

        $status = app(ShiftPlanWorkflowStatusService::class)
            ->computeForDateRange(Carbon::parse('2026-05-04'), Carbon::parse('2026-05-10'))['user'][$member->id];

        // Schicht festgeschrieben, individuelle Zeit noch angefragt → angefragt
        $this->assertSame('requested', $status[$craftCommitted->id][19]);
        $this->assertSame('requested', $status[ShiftPlanWorkflowStatusService::INDIVIDUAL_TIMES_KEY][19]);

        $time->forceFill(['in_workflow' => false, 'is_committed' => true])->save();
        $status = app(ShiftPlanWorkflowStatusService::class)
            ->computeForDateRange(Carbon::parse('2026-05-04'), Carbon::parse('2026-05-10'))['user'][$member->id];

        $this->assertSame('committed', $status[$craftCommitted->id][19]);
        // Offene Schicht im anderen Gewerk: nicht grün, und kein Rückfall auf den Wert der individuellen Zeiten
        $this->assertSame('open', $status[$craftOpen->id][19]);
        $this->assertSame('committed', $status[ShiftPlanWorkflowStatusService::INDIVIDUAL_TIMES_KEY][19]);
    }

    #[Test]
    public function the_week_status_page_counts_released_individual_times_of_the_craft(): void
    {
        $this->actingAsAdmin();
        [$craft, $member] = $this->craftWithMember();
        $this->individualTimeFor($member);
        $request = $this->submitRequest($craft);

        $week = fn (): array => app(ShiftWeekStatusService::class)
            ->compute(Carbon::parse('2026-05-04'), Carbon::parse('2026-05-10'), [$craft->id])
            ['rows'][$craft->id]['2026-W19'];

        $pendingWeek = $week();
        $this->assertSame('requested', $pendingWeek['status']);
        $this->assertSame(1, $pendingWeek['individual_times_total']);
        $this->assertSame(0, $pendingWeek['individual_times_committed']);

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        $approvedWeek = $week();
        $this->assertSame('committed', $approvedWeek['status']);
        $this->assertSame(1, $approvedWeek['individual_times_committed']);
    }

    #[Test]
    public function the_review_shows_the_release_status_of_each_individual_time(): void
    {
        $this->actingAsAdmin();
        [$craftA, $trainee] = $this->craftWithMember();
        $craftB = Craft::factory()->create();
        $craftB->users()->attach($trainee->id);
        $time = $this->individualTimeFor($trainee);
        $requestA = $this->submitRequest($craftA);
        $requestB = $this->submitRequest($craftB);

        $this->get(route('shift-plan-requests.show', $requestA))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('individualTimes.0.id', $time->id)
                ->where('individualTimes.0.workflow_status', 'requested')
                ->where('individualTimes.0.in_this_request', true));

        $this->get(route('shift-plan-requests.show', $requestB))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('individualTimes.0.id', $time->id)
                ->where('individualTimes.0.in_this_request', false));
    }
}
