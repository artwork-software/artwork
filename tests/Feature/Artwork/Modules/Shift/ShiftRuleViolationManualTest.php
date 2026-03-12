<?php

namespace Tests\Feature\Artwork\Modules\Shift;

use Tests\TestCase;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\EventType\Models\EventType;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for manual violations, compensation processing, granting, and deadline expiry
 */
class ShiftRuleViolationManualTest extends TestCase
{
    private User $user;
    private User $adminUser;
    private UserContract $contract;
    private Project $project;
    private Room $room;
    private Event $event;
    private Craft $craft;
    private ShiftQualification $shiftQualification;
    private EventType $eventType;
    private ShiftRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $this->adminUser = $this->adminUser();

        $this->user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'email' => 'test.worker.manual@example.com'
        ]);

        $this->contract = \Database\Factories\UserContractFactory::new()->create([
            'name' => 'Standard Contract'
        ]);

        \Database\Factories\UserContractAssignFactory::new()->create([
            'user_id' => $this->user->id,
            'user_contract_id' => $this->contract->id
        ]);

        $this->project = Project::factory()->create(['name' => 'Test Project']);
        $this->room = Room::factory()->create(['name' => 'Test Room']);
        $this->craft = Craft::factory()->create(['name' => 'Test Craft']);
        $this->shiftQualification = \Database\Factories\Artwork\Modules\ShiftQualification\Models\ShiftQualificationFactory::new()->create(['name' => 'Test Qualification']);
        $this->eventType = \Database\Factories\Artwork\Modules\EventType\Models\EventTypeFactory::new()->create(['name' => 'Test Event Type']);

        $this->event = Event::factory()->create([
            'name' => 'Test Event',
            'project_id' => $this->project->id,
            'room_id' => $this->room->id,
            'event_type_id' => $this->eventType->id
        ]);

        $this->rule = ShiftRule::create([
            'name' => 'Max 8 Hours',
            'description' => 'Maximum 8 working hours per day',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);
        $this->rule->contracts()->attach($this->contract->id);

        $this->actingAs($this->adminUser);
    }

    // ── Manual Violation Creation ──────────────────────────────────

    #[Test]
    public function testCanCreateManualViolation(): void
    {
        $response = $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
            'violation_date' => now()->format('Y-m-d'),
            'reason' => 'Manually recorded violation',
            'severity' => 'warning',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('shift_rule_violations', [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
            'is_manual' => true,
            'created_by_user_id' => $this->adminUser->id,
            'reason' => 'Manually recorded violation',
            'severity' => 'warning',
            'status' => 'active',
            'shift_id' => null,
        ]);
    }

    #[Test]
    public function testManualViolationWithErrorSeverity(): void
    {
        $response = $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
            'violation_date' => now()->format('Y-m-d'),
            'severity' => 'error',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('shift_rule_violations', [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
            'severity' => 'error',
            'is_manual' => true,
        ]);
    }

    #[Test]
    public function testManualViolationRequiresUserId(): void
    {
        $response = $this->post(route('shift-rule-violations.manual.store'), [
            'shift_rule_id' => $this->rule->id,
            'violation_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['user_id']);
    }

    #[Test]
    public function testManualViolationRequiresShiftRuleId(): void
    {
        $response = $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $this->user->id,
            'violation_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['shift_rule_id']);
    }

    #[Test]
    public function testManualViolationRequiresDate(): void
    {
        $response = $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
        ]);

        $response->assertSessionHasErrors(['violation_date']);
    }

    #[Test]
    public function testManualViolationHasNoShiftId(): void
    {
        $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
            'violation_date' => now()->format('Y-m-d'),
        ]);

        $violation = ShiftRuleViolation::where('user_id', $this->user->id)
            ->where('is_manual', true)
            ->first();

        $this->assertNotNull($violation);
        $this->assertNull($violation->shift_id);
    }

    // ── Compensation Processing ──────────────────────────────────

    #[Test]
    public function testCanProcessViolationWithCompensation(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->put(route('shift-rule-violations.process', $violation), [
            'compensation_days' => 1.5,
            'compensation_deadline' => now()->addDays(30)->format('Y-m-d'),
            'compensation_reason' => 'Overtime compensation',
        ]);

        $response->assertRedirect();

        $violation->refresh();
        $this->assertEquals(1.5, (float) $violation->compensation_days);
        $this->assertNotNull($violation->compensation_deadline);
        $this->assertEquals('Overtime compensation', $violation->compensation_reason);
    }

    #[Test]
    public function testProcessViolationRequiresCompensationDays(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->put(route('shift-rule-violations.process', $violation), [
            'compensation_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['compensation_days']);
    }

    #[Test]
    public function testProcessViolationRequiresDeadline(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->put(route('shift-rule-violations.process', $violation), [
            'compensation_days' => 1.0,
        ]);

        $response->assertSessionHasErrors(['compensation_deadline']);
    }

    #[Test]
    public function testProcessViolationDeadlineMustBeInFuture(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->put(route('shift-rule-violations.process', $violation), [
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['compensation_deadline']);
    }

    #[Test]
    public function testProcessViolationMinimumHalfDay(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->put(route('shift-rule-violations.process', $violation), [
            'compensation_days' => 0.1,
            'compensation_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['compensation_days']);
    }

    #[Test]
    public function testCanProcessWithHalfDaySteps(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->put(route('shift-rule-violations.process', $violation), [
            'compensation_days' => 0.5,
            'compensation_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $violation->refresh();
        $this->assertEquals(0.5, (float) $violation->compensation_days);
    }

    // ── Grant Compensation ──────────────────────────────────────

    #[Test]
    public function testCanGrantCompensation(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $response = $this->post(route('shift-rule-violations.grant', $violation));

        $response->assertRedirect();

        $violation->refresh();
        $this->assertNotNull($violation->compensation_granted_at);
        $this->assertEquals($this->adminUser->id, $violation->compensation_granted_by);
        $this->assertEquals('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
    }

    #[Test]
    public function testCannotGrantCompensationWithoutDays(): void
    {
        $violation = $this->createActiveViolation();
        // No compensation_days set

        $response = $this->post(route('shift-rule-violations.grant', $violation));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $violation->refresh();
        $this->assertNull($violation->compensation_granted_at);
        $this->assertEquals('active', $violation->status);
    }

    // ── Model Methods ──────────────────────────────────────────

    #[Test]
    public function testHasCompensationReturnsTrueWhenDaysSet(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update(['compensation_days' => 1.0]);

        $this->assertTrue($violation->hasCompensation());
    }

    #[Test]
    public function testHasCompensationReturnsFalseWhenNoDays(): void
    {
        $violation = $this->createActiveViolation();

        $this->assertFalse($violation->hasCompensation());
    }

    #[Test]
    public function testIsCompensationOverdueWhenDeadlinePassed(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1),
        ]);

        $this->assertTrue($violation->isCompensationOverdue());
    }

    #[Test]
    public function testIsCompensationNotOverdueWhenGranted(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1),
            'compensation_granted_at' => now(),
            'compensation_granted_by' => $this->adminUser->id,
        ]);

        $this->assertFalse($violation->isCompensationOverdue());
    }

    #[Test]
    public function testIsCompensationNotOverdueWhenDeadlineInFuture(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $this->assertFalse($violation->isCompensationOverdue());
    }

    // ── Scopes ──────────────────────────────────────────────────

    #[Test]
    public function testOpenCompensationScope(): void
    {
        $openViolation = $this->createActiveViolation();
        $openViolation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $grantedViolation = $this->createActiveViolation();
        $grantedViolation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
            'compensation_granted_at' => now(),
            'compensation_granted_by' => $this->adminUser->id,
            'status' => 'resolved',
        ]);

        $openCompensations = ShiftRuleViolation::openCompensation()->get();

        $this->assertTrue($openCompensations->contains('id', $openViolation->id));
        $this->assertFalse($openCompensations->contains('id', $grantedViolation->id));
    }

    #[Test]
    public function testGrantedCompensationScope(): void
    {
        $grantedViolation = $this->createActiveViolation();
        $grantedViolation->update([
            'compensation_days' => 1.0,
            'compensation_granted_at' => now(),
            'compensation_granted_by' => $this->adminUser->id,
        ]);

        $ungrantedViolation = $this->createActiveViolation();
        $ungrantedViolation->update([
            'compensation_days' => 1.0,
        ]);

        $granted = ShiftRuleViolation::grantedCompensation()->get();

        $this->assertTrue($granted->contains('id', $grantedViolation->id));
        $this->assertFalse($granted->contains('id', $ungrantedViolation->id));
    }

    #[Test]
    public function testOverdueCompensationScope(): void
    {
        $overdueViolation = $this->createActiveViolation();
        $overdueViolation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1),
        ]);

        $futureViolation = $this->createActiveViolation();
        $futureViolation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $overdue = ShiftRuleViolation::overdueCompensation()->get();

        $this->assertTrue($overdue->contains('id', $overdueViolation->id));
        $this->assertFalse($overdue->contains('id', $futureViolation->id));
    }

    // ── Relationships ──────────────────────────────────────────

    #[Test]
    public function testCreatedByUserRelationship(): void
    {
        $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $this->user->id,
            'shift_rule_id' => $this->rule->id,
            'violation_date' => now()->format('Y-m-d'),
            'reason' => 'Test reason',
        ]);

        $violation = ShiftRuleViolation::where('user_id', $this->user->id)
            ->where('is_manual', true)
            ->first();

        $this->assertNotNull($violation->createdByUser);
        $this->assertEquals($this->adminUser->id, $violation->createdByUser->id);
    }

    #[Test]
    public function testGrantedByUserRelationship(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $violation->grantCompensation($this->adminUser->id);
        $violation->refresh();

        $this->assertNotNull($violation->grantedByUser);
        $this->assertEquals($this->adminUser->id, $violation->grantedByUser->id);
    }

    #[Test]
    public function testParentChildViolationRelationship(): void
    {
        $parentViolation = $this->createActiveViolation();

        $childViolation = ShiftRuleViolation::create([
            'shift_rule_id' => $this->rule->id,
            'user_id' => $this->user->id,
            'violation_date' => now(),
            'severity' => 'error',
            'status' => 'active',
            'parent_violation_id' => $parentViolation->id,
            'reason' => 'Deadline expired',
        ]);

        $this->assertEquals($parentViolation->id, $childViolation->parentViolation->id);
        $this->assertTrue($parentViolation->fresh()->childViolations->contains('id', $childViolation->id));
    }

    // ── Ignore Violation ──────────────────────────────────────

    #[Test]
    public function testCanIgnoreViolation(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->post(route('shift-rule-violations.ignore', $violation));

        $response->assertRedirect();
        $violation->refresh();
        $this->assertEquals('ignored', $violation->status);
        $this->assertNotNull($violation->resolved_at);
    }

    // ── Active Rules Endpoint ──────────────────────────────────

    #[Test]
    public function testActiveRulesEndpointReturnsOnlyActiveRules(): void
    {
        $inactiveRule = ShiftRule::create([
            'name' => 'Inactive Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 4.0,
            'warning_color' => '#00ff00',
            'is_active' => false,
        ]);

        $response = $this->get(route('shift-rules.active'));

        $response->assertOk();
        $data = $response->json();

        $ruleIds = array_column($data, 'id');
        $this->assertContains($this->rule->id, $ruleIds);
        $this->assertNotContains($inactiveRule->id, $ruleIds);
    }

    // ── Violations for Date Range ──────────────────────────────

    #[Test]
    public function testCanGetViolationsForDateRange(): void
    {
        $violation = $this->createActiveViolation();

        $response = $this->get(route('shift-rule-violations.date-range', [
            'start_date' => now()->subDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(1)->format('Y-m-d'),
            'user_ids' => [$this->user->id],
        ]));

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey((string) $this->user->id, $data);
    }

    #[Test]
    public function testViolationsForDateRangeExcludesResolvedViolations(): void
    {
        $activeViolation = $this->createActiveViolation();

        $resolvedViolation = ShiftRuleViolation::create([
            'shift_rule_id' => $this->rule->id,
            'user_id' => $this->user->id,
            'violation_date' => now(),
            'severity' => 'warning',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $response = $this->get(route('shift-rule-violations.date-range', [
            'start_date' => now()->subDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(1)->format('Y-m-d'),
            'user_ids' => [$this->user->id],
        ]));

        $response->assertOk();
        $data = $response->json();

        // Flatten all violations for the user
        $violationIds = [];
        foreach ($data[(string) $this->user->id] ?? [] as $dateViolations) {
            foreach ($dateViolations as $v) {
                $violationIds[] = $v['id'];
            }
        }

        $this->assertContains($activeViolation->id, $violationIds);
        $this->assertNotContains($resolvedViolation->id, $violationIds);
    }

    // ── User Compensation Days Page ──────────────────────────────

    #[Test]
    public function testCanAccessUserCompensationDaysPage(): void
    {
        $response = $this->get(route('user.edit.compensationDays', $this->user));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Users/UserCompensationDays')
            ->has('userToEdit')
            ->has('openCompensations')
            ->has('grantedCompensations')
            ->has('unprocessedViolations')
        );
    }

    #[Test]
    public function testCompensationDaysPageShowsOpenCompensations(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.5,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $response = $this->get(route('user.edit.compensationDays', $this->user));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('openCompensations', 1)
        );
    }

    #[Test]
    public function testCompensationDaysPageShowsGrantedCompensations(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->addDays(30),
            'compensation_granted_at' => now(),
            'compensation_granted_by' => $this->adminUser->id,
            'status' => 'resolved',
        ]);

        $response = $this->get(route('user.edit.compensationDays', $this->user));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('grantedCompensations', 1)
        );
    }

    #[Test]
    public function testCompensationDaysPageShowsUnprocessedViolations(): void
    {
        $this->createActiveViolation(); // no compensation_days set

        $response = $this->get(route('user.edit.compensationDays', $this->user));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('unprocessedViolations', 1)
        );
    }

    // ── Deadline Expiry Command ──────────────────────────────────

    #[Test]
    public function testOverdueCompensationCreatesChildViolation(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1),
        ]);

        $this->artisan('shift-rules:validate', ['--days' => 1])
            ->assertExitCode(0);

        $childViolation = ShiftRuleViolation::where('parent_violation_id', $violation->id)->first();

        $this->assertNotNull($childViolation, 'Child violation should be created for overdue compensation');
        $this->assertEquals('error', $childViolation->severity);
        $this->assertEquals($this->user->id, $childViolation->user_id);
        $this->assertEquals('Ersatzfrei-Frist abgelaufen', $childViolation->reason);
    }

    #[Test]
    public function testOverdueCompensationDoesNotDuplicateChildViolation(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1),
        ]);

        // Run command twice
        $this->artisan('shift-rules:validate', ['--days' => 1]);
        $this->artisan('shift-rules:validate', ['--days' => 1]);

        $childViolationCount = ShiftRuleViolation::where('parent_violation_id', $violation->id)->count();
        $this->assertEquals(1, $childViolationCount, 'Only one child violation should be created');
    }

    #[Test]
    public function testGrantedCompensationDoesNotTriggerOverdue(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 1.0,
            'compensation_deadline' => now()->subDays(1),
            'compensation_granted_at' => now()->subDays(2),
            'compensation_granted_by' => $this->adminUser->id,
            'status' => 'resolved',
        ]);

        $this->artisan('shift-rules:validate', ['--days' => 1]);

        $childViolationCount = ShiftRuleViolation::where('parent_violation_id', $violation->id)->count();
        $this->assertEquals(0, $childViolationCount, 'Granted compensation should not trigger overdue');
    }

    // ── Grant Compensation via Model Method ──────────────────

    #[Test]
    public function testGrantCompensationModelMethod(): void
    {
        $violation = $this->createActiveViolation();
        $violation->update([
            'compensation_days' => 2.0,
            'compensation_deadline' => now()->addDays(30),
        ]);

        $violation->grantCompensation($this->adminUser->id);
        $violation->refresh();

        $this->assertNotNull($violation->compensation_granted_at);
        $this->assertEquals($this->adminUser->id, $violation->compensation_granted_by);
        $this->assertEquals('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->adminUser->id, $violation->resolved_by);
    }

    // ── Helper ──────────────────────────────────────────────────

    private function createActiveViolation(): ShiftRuleViolation
    {
        return ShiftRuleViolation::create([
            'shift_rule_id' => $this->rule->id,
            'user_id' => $this->user->id,
            'violation_date' => now(),
            'violation_data' => ['planned_hours' => 10, 'max_allowed' => 8],
            'severity' => 'warning',
            'status' => 'active',
        ]);
    }
}
