<?php

namespace Tests\Feature\Artwork\Modules\Shift;

use Tests\TestCase;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Console\Commands\ValidateShiftRulesCommand;
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
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;

/**
 * Umfassender Test für das neue ShiftRule-System
 * Zeigt das komplette Szenario von Regelverletzungen, Notifications und Commands
 */
class ShiftRuleSimplifiedTest extends TestCase
{
    private ShiftRuleService $shiftRuleService;
    private User $user;
    private UserContract $contract;
    private ShiftRule $maxHoursRule;
    private Project $project;
    private Room $room;
    private Event $event;
    private Craft $craft;
    private ShiftQualification $shiftQualification;
    private EventType $eventType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shiftRuleService = app(ShiftRuleService::class);
        $this->setupSimpleTestData();
    }

    private function setupSimpleTestData(): void
    {
        // Create User using factory
        $this->user = \Artwork\Modules\User\Models\User::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max.mustermann@test.de'
        ]);

        // Create Contract using factory
        $this->contract = \Database\Factories\UserContractFactory::new()->create([
            'name' => 'Test Vollzeit-Vertrag',
            'description' => 'Standard Vollzeitvertrag'
        ]);

        // Assign User to Contract
        \Database\Factories\UserContractAssignFactory::new()->create([
            'user_id' => $this->user->id,
            'user_contract_id' => $this->contract->id
        ]);

        // Create basic entities using factories
        $this->project = \Artwork\Modules\Project\Models\Project::factory()->create(['name' => 'Test Projekt']);
        $this->room = \Artwork\Modules\Room\Models\Room::factory()->create(['name' => 'Test Raum']);
        $this->craft = \Artwork\Modules\Craft\Models\Craft::factory()->create(['name' => 'Test Craft']);
        $this->shiftQualification = \Database\Factories\Artwork\Modules\ShiftQualification\Models\ShiftQualificationFactory::new()->create(['name' => 'Test Qualification']);
        $this->eventType = \Database\Factories\Artwork\Modules\EventType\Models\EventTypeFactory::new()->create(['name' => 'Test Event Type']);

        $this->event = \Artwork\Modules\Event\Models\Event::factory()->create([
            'name' => 'Test Event',
            'project_id' => $this->project->id,
            'room_id' => $this->room->id,
            'event_type_id' => $this->eventType->id,
            'eventName' => 'Test Event'
        ]);

        // Create Shift Rule for max hours per day
        $this->maxHoursRule = new ShiftRule([
            'name' => 'Maximal 8 Stunden pro Tag',
            'description' => 'Niemand soll mehr als 8 Stunden pro Tag arbeiten',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);
        $this->maxHoursRule->save();

        // Assign Rule to Contract
        $this->maxHoursRule->contracts()->attach($this->contract->id);
    }

    #[Test]
    public function testShiftRuleViolationWorkflow(): void
    {
        // Step 1: Create a shift that violates the daily max hours rule (10 hours instead of 8)
        $shiftDate = now()->addDays(1);

        $longShift = new Shift([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00', // 10 hours total
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Überlange Schicht für Test',
            'is_committed' => false
        ]);
        $longShift->save();

        // Assign user to shift
        $longShift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Step 2: Test that service can get rules for user
        $userContract = $this->user->activeWorkContract();
        $this->assertNotNull($userContract, 'User should have active contract');

        // Step 3: Run validation service
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        // Step 4: Verify violation was created and stored
        $this->assertGreaterThan(0, $violations->count(), 'Should create at least one violation');

        $violation = $violations->first();
        $this->assertEquals($this->maxHoursRule->id, $violation->shift_rule_id);
        $this->assertEquals($this->user->id, $violation->user_id);
        $this->assertEquals($longShift->id, $violation->shift_id);
        $this->assertEquals('warning', $violation->severity);
        $this->assertEquals('active', $violation->status);

        // Step 5: Check violation data contains expected information
        $violationData = $violation->violation_data;
        $this->assertArrayHasKey('planned_hours', $violationData);
        $this->assertArrayHasKey('max_allowed', $violationData);
        $this->assertEquals(10, $violationData['planned_hours']);
        $this->assertEquals(8, $violationData['max_allowed']);

        // Step 6: Test violation exists in database
        $dbViolation = ShiftRuleViolation::where('shift_id', $longShift->id)->first();
        $this->assertNotNull($dbViolation);
        $this->assertEquals($this->maxHoursRule->id, $dbViolation->shift_rule_id);

        // Step 7: Test violation helper methods
        $this->assertEquals('#ff0000', $violation->getWarningColor());
        $this->assertStringContainsString('8 Stunden', $violation->getViolationMessage());
        $this->assertFalse($violation->isResolved());

        // Step 8: Test violation resolution
        $violation->resolve($this->user->id);
        $violation->refresh();

        $this->assertEquals('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by);
        $this->assertTrue($violation->isResolved());

        // Step 9: Test ignore functionality
        $violation->update(['status' => 'active', 'resolved_at' => null, 'resolved_by' => null]);
        $violation->ignore($this->user->id);
        $violation->refresh();

        $this->assertEquals('ignored', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by);
    }

    #[Test]
    public function testNoViolationForCompliantShift(): void
    {
        // Create compliant shift (7 hours, well within 8-hour limit)
        $shiftDate = now()->addDays(1);

        $normalShift = new Shift([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '09:00:00',
            'end' => '16:00:00', // 7 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Normale Schicht',
            'is_committed' => false
        ]);
        $normalShift->save();

        $normalShift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        // Should have no violations
        $this->assertCount(0, $violations, 'Compliant shift should not create violations');
    }

    #[Test]
    public function testInactiveRulesAreIgnored(): void
    {
        // Deactivate the max hours rule
        $this->maxHoursRule->update(['is_active' => false]);

        // Create violating shift (12 hours)
        $shiftDate = now()->addDays(1);

        $longShift = new Shift([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '06:00:00',
            'end' => '18:00:00', // 12 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Sehr lange Schicht',
            'is_committed' => false
        ]);
        $longShift->save();

        $longShift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        // Should have no violations because rule is inactive
        $this->assertCount(0, $violations, 'Inactive rules should not create violations');
    }

    #[Test]
    public function testCommandValidation(): void
    {
        // Create violation scenario
        $shiftDate = now()->addDays(2);

        $shift = new Shift([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '06:00:00',
            'end' => '20:00:00', // 14 hours - clear violation
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Sehr lange Schicht',
            'is_committed' => false
        ]);
        $shift->save();

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run the validation command
        $exitCode = Artisan::call('shift-rules:validate', ['--days' => 7]);

        // Command should complete successfully
        $this->assertEquals(0, $exitCode, 'Command should complete successfully');

        // Check command output contains relevant information
        $output = Artisan::output();
        $this->assertStringContainsString('Validating shift rules', $output);
        $this->assertStringContainsString('Found', $output);
        $this->assertStringContainsString('rule violations', $output);

        // Check that violation was created
        $violations = ShiftRuleViolation::where('shift_id', $shift->id)->get();
        $this->assertGreaterThan(0, $violations->count(), 'Command should create violations');

        $violation = $violations->first();
        $this->assertEquals($this->maxHoursRule->id, $violation->shift_rule_id);
        $this->assertEquals(14, $violation->violation_data['planned_hours']);
        $this->assertEquals(8, $violation->violation_data['max_allowed']);
    }

    #[Test]
    public function testMultipleViolationsOnSameShift(): void
    {
        // Create a consecutive days rule as well
        $consecutiveDaysRule = new ShiftRule([
            'name' => 'Maximal 5 Tage in Folge',
            'description' => 'Maximal 5 aufeinanderfolgende Arbeitstage',
            'trigger_type' => 'maxConsecWorkingDays',
            'individual_number_value' => 5.0,
            'warning_color' => '#ff8800',
            'is_active' => true
        ]);
        $consecutiveDaysRule->save();
        $consecutiveDaysRule->contracts()->attach($this->contract->id);

        $startDate = now()->startOfWeek();

        // Create 5 normal shifts first (Monday to Friday)
        for ($i = 0; $i < 5; $i++) {
            $shiftDate = $startDate->copy()->addDays($i);

            $event = new Event([
                'name' => "Event Tag $i",
                'project_id' => $this->project->id,
                'room_id' => $this->room->id,
                'event_type_id' => $this->eventType->id,
                'start_time' => $shiftDate->copy()->setHour(9)->setMinute(0)->setSecond(0),
                'end_time' => $shiftDate->copy()->setHour(17)->setMinute(0)->setSecond(0)
            ]);
            $event->save();

            $shift = new Shift([
                'event_id' => $event->id,
                'start_date' => $shiftDate->format('Y-m-d'),
                'end_date' => $shiftDate->format('Y-m-d'),
                'start' => '09:00:00',
                'end' => '17:00:00', // 8 hours
                'break_minutes' => 0,
                'craft_id' => $this->craft->id,
                'description' => "Schicht Tag $i",
                'is_committed' => false
            ]);
            $shift->save();
            $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);
        }

        // Day 6: Long shift (12 hours) + 6th consecutive day
        $violationDate = $startDate->copy()->addDays(5);

        $violationEvent = new Event([
            'name' => 'Violation Event',
            'project_id' => $this->project->id,
            'room_id' => $this->room->id,
            'event_type_id' => $this->eventType->id,
            'start_time' => $violationDate->copy()->setHour(8)->setMinute(0)->setSecond(0),
            'end_time' => $violationDate->copy()->setHour(20)->setMinute(0)->setSecond(0)
        ]);
        $violationEvent->save();

        $violationShift = new Shift([
            'event_id' => $violationEvent->id,
            'start_date' => $violationDate->format('Y-m-d'),
            'end_date' => $violationDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '20:00:00', // 12 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Problematische Schicht',
            'is_committed' => false
        ]);
        $violationShift->save();
        $violationShift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation for the full week
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $startDate,
            $startDate->copy()->addDays(6)
        );

        // Should have violations for both rules
        $this->assertGreaterThan(1, $violations->count(), 'Should have multiple violations');

        $maxHoursViolations = $violations->where('shift_rule_id', $this->maxHoursRule->id);
        $consecutiveViolations = $violations->where('shift_rule_id', $consecutiveDaysRule->id);

        $this->assertGreaterThan(0, $maxHoursViolations->count(), 'Should have max hours violation');
        $this->assertGreaterThan(0, $consecutiveViolations->count(), 'Should have consecutive days violation');

        // Verify violation details
        $maxHoursViolation = $maxHoursViolations->first();
        $this->assertEquals(12, $maxHoursViolation->violation_data['planned_hours']);

        $consecutiveViolation = $consecutiveViolations->first();
        $this->assertEquals(6, $consecutiveViolation->violation_data['consecutive_days']);
    }

    #[Test]
    public function testShiftRuleModelRelationships(): void
    {
        // Test that ShiftRule has correct relationships
        $this->assertTrue($this->maxHoursRule->contracts->contains($this->contract));
        $this->assertTrue($this->maxHoursRule->is_active);
        $this->assertEquals('maxWorkingHoursOnDay', $this->maxHoursRule->trigger_type);
        $this->assertEquals(8.0, $this->maxHoursRule->individual_number_value);
        $this->assertEquals('#ff0000', $this->maxHoursRule->warning_color);

        // Test contract relationship
        $this->assertTrue($this->contract->shiftRules->contains($this->maxHoursRule));
    }
}
