<?php

namespace Tests\Feature\Artwork\Modules\Shift;

use Tests\TestCase;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\EventType\Models\EventType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Comprehensive controller tests for the ShiftRule system
 */
class ShiftRuleControllerTest extends TestCase
{
    private User $user;
    private UserContract $contract;
    private Project $project;
    private Room $room;
    private Event $event;
    private Craft $craft;
    private ShiftQualification $shiftQualification;
    private EventType $eventType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        // Create User
        $this->user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user@example.com'
        ]);

        // Create Contract
        $this->contract = \Database\Factories\UserContractFactory::new()->create([
            'name' => 'Test Contract'
        ]);

        // Assign User to Contract
        \Database\Factories\UserContractAssignFactory::new()->create([
            'user_id' => $this->user->id,
            'user_contract_id' => $this->contract->id
        ]);

        // Create supporting entities
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

        $this->actingAs($this->user);
    }

    #[Test]
    public function testCanCreateShiftRule(): void
    {
        $ruleData = [
            'name' => 'Test Rule',
            'description' => 'Test Description',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'contract_ids' => [$this->contract->id],
            'user_ids' => [$this->user->id]
        ];

        $response = $this->post(route('shift-rules.store'), $ruleData);

        $response->assertRedirect();
        $this->assertDatabaseHas('shift_rules', [
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0
        ]);

        $rule = ShiftRule::where('name', 'Test Rule')->first();
        $this->assertTrue($rule->contracts->contains($this->contract));
        $this->assertTrue($rule->usersToNotify->contains($this->user));
    }

    #[Test]
    public function testCanUpdateShiftRule(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Original Rule',
            'description' => 'Original Description',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $this->assertNotNull($rule->id, 'Rule should have an ID after creation');

        $updateData = [
            'name' => 'Updated Rule',
            'description' => 'Updated Description',
            'individual_number_value' => 10.0,
            'warning_color' => '#ff8800',
            'contract_ids' => [$this->contract->id]
        ];

        $response = $this->put(route('shift-rules.update', $rule->id), $updateData);

        $response->assertRedirect();
        $rule->refresh();
        $this->assertEquals('Updated Rule', $rule->name);
        $this->assertEquals(10.0, $rule->individual_number_value);
        $this->assertEquals('#ff8800', $rule->warning_color);
        $this->assertTrue($rule->contracts->contains($this->contract));
    }

    #[Test]
    public function testCanDeleteShiftRule(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Rule to Delete',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $response = $this->delete(route('shift-rules.destroy', $rule));

        $response->assertRedirect();
        $this->assertSoftDeleted($rule);
    }

    #[Test]
    public function testCanViewContractAssignments(): void
    {
        $response = $this->get(route('shift-rules.contracts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ShiftRules/ContractAssignments')
            ->has('contracts')
            ->has('rules')
        );
    }

    #[Test]
    public function testCanUpdateContractAssignments(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $response = $this->put(route('shift-rules.contracts.assignments.update', $this->contract), [
            'rule_ids' => [$rule->id]
        ]);

        $response->assertRedirect();
        $this->assertTrue($this->contract->fresh()->shiftRules->contains($rule));
    }

    #[Test]
    public function testCanAssignContractsToRule(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $response = $this->post(route('shift-rules.contracts.assign', $rule), [
            'contract_ids' => [$this->contract->id]
        ]);

        $response->assertRedirect();
        $this->assertTrue($rule->fresh()->contracts->contains($this->contract));
    }

    #[Test]
    public function testCanAssignUsersToRule(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $response = $this->post(route('shift-rules.users.assign', $rule), [
            'user_ids' => [$this->user->id]
        ]);

        $response->assertRedirect();
        $this->assertTrue($rule->fresh()->usersToNotify->contains($this->user));
    }

    #[Test]
    public function testCanValidateRules(): void
    {
        // Create a rule
        $rule = ShiftRule::create([
            'name' => 'Max 8 Hours',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);
        $rule->contracts()->attach($this->contract->id);

        // Create a violating shift
        $shiftDate = now()->addDays(1);
        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00', // 10 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'is_committed' => false
        ]);
        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        $response = $this->post(route('shift-rules.validate'), [
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'user_id' => $this->user->id
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('violations')
            ->where('violationsCount', 1)
        );
    }

    #[Test]
    public function testCanGetPendingViolations(): void
    {
        // Create a rule and violation
        $rule = ShiftRule::create([
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $shiftDate = now()->addDays(1);
        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00',
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'is_committed' => false
        ]);

        $violation = ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'violation_date' => $shiftDate,
            'violation_data' => ['planned_hours' => 10, 'max_allowed' => 8],
            'severity' => 'warning',
            'status' => 'active'
        ]);

        $response = $this->get(route('shift-rules.pending'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('pendingViolations')
            ->where('pendingViolationsCount', 1)
        );
    }

    #[Test]
    public function testCanResolveViolation(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00',
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'is_committed' => false
        ]);

        $violation = ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'violation_date' => now(),
            'violation_data' => ['planned_hours' => 10, 'max_allowed' => 8],
            'severity' => 'warning',
            'status' => 'active'
        ]);

        $response = $this->post(route('shift-rule-violations.resolve', $violation));

        $response->assertRedirect();
        $violation->refresh();
        $this->assertEquals('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by);
    }

    #[Test]
    public function testCanIgnoreViolation(): void
    {
        $rule = ShiftRule::create([
            'name' => 'Test Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00',
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'is_committed' => false
        ]);

        $violation = ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'violation_date' => now(),
            'violation_data' => ['planned_hours' => 10, 'max_allowed' => 8],
            'severity' => 'warning',
            'status' => 'active'
        ]);

        $response = $this->post(route('shift-rule-violations.ignore', $violation));

        $response->assertRedirect();
        $violation->refresh();
        $this->assertEquals('ignored', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by);
    }

    #[Test]
    public function testValidationFailsWithInvalidData(): void
    {
        $response = $this->post(route('shift-rules.store'), [
            'name' => '', // Required field missing
            'trigger_type' => 'invalid_type', // Invalid trigger type
            'individual_number_value' => -1, // Invalid number
        ]);

        $response->assertSessionHasErrors(['name', 'trigger_type', 'individual_number_value']);
    }

    #[Test]
    public function testOnlyActiveRulesAreValidated(): void
    {
        // Create inactive rule
        $inactiveRule = ShiftRule::create([
            'name' => 'Inactive Rule',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 4.0, // Very restrictive
            'warning_color' => '#ff0000',
            'is_active' => false // Inactive
        ]);
        $inactiveRule->contracts()->attach($this->contract->id);

        // Create violating shift
        $shiftDate = now()->addDays(1);
        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00', // 10 hours - would violate if active
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'is_committed' => false
        ]);
        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        $response = $this->post(route('shift-rules.validate'), [
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'user_id' => $this->user->id
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('violationsCount', 0) // No violations because rule is inactive
        );
    }
}
