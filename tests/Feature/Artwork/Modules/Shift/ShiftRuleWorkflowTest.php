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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class ShiftRuleWorkflowTest extends TestCase
{
    private ShiftRuleService $shiftRuleService;
    private User $user;
    private UserContract $contract;
    private ShiftRule $maxHoursRule;
    private ShiftRule $consecutiveDaysRule;
    private Project $project;
    private Room $room;
    private Event $event;
    private Craft $craft;
    private \Artwork\Modules\Shift\Models\ShiftQualification $shiftQualification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shiftRuleService = app(ShiftRuleService::class);

        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        // Create User
        $this->user = \Artwork\Modules\User\Models\User::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max.mustermann@test.de'
        ]);

        // Create Contract
        $this->contract = \Database\Factories\UserContractFactory::new()->create([
            'name' => 'Vollzeit-Vertrag',
            'description' => 'Standard Vollzeitvertrag mit 40h/Woche'
        ]);

        // Assign User to Contract
        \Database\Factories\UserContractAssignFactory::new()->create([
            'user_id' => $this->user->id,
            'user_contract_id' => $this->contract->id
        ]);

        // Create Project, Room, Event, Craft for Shifts
        $this->project = \Artwork\Modules\Project\Models\Project::factory()->create(['name' => 'Test Projekt']);
        $this->room = \Artwork\Modules\Room\Models\Room::factory()->create(['name' => 'Test Raum']);
        $this->craft = \Artwork\Modules\Craft\Models\Craft::factory()->create(['name' => 'Test Craft']);
        $this->shiftQualification = \Database\Factories\Artwork\Modules\ShiftQualification\Models\ShiftQualificationFactory::new()->create(['name' => 'Test Qualification']);

        $this->event = \Artwork\Modules\Event\Models\Event::factory()->create([
            'name' => 'Test Event',
            'project_id' => $this->project->id,
            'room_id' => $this->room->id,
            'start_time' => now()->setTimeFromTimeString('08:00:00'),
            'end_time' => now()->setTimeFromTimeString('18:00:00'),
            'eventName' => 'Test Event'
        ]);

        // Create Shift Rules
        $this->maxHoursRule = ShiftRule::create([
            'name' => 'Maximal 8 Stunden pro Tag',
            'description' => 'Niemand soll mehr als 8 Stunden pro Tag arbeiten',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $this->consecutiveDaysRule = ShiftRule::create([
            'name' => 'Maximal 5 Tage in Folge',
            'description' => 'Maximal 5 aufeinanderfolgende Arbeitstage',
            'trigger_type' => 'maxConsecWorkingDays',
            'individual_number_value' => 5.0,
            'warning_color' => '#ff8800',
            'is_active' => true
        ]);

        // Assign Rules to Contract
        $this->maxHoursRule->contracts()->attach($this->contract->id);
        $this->consecutiveDaysRule->contracts()->attach($this->contract->id);

        // Setup Notifications Mock
        Notification::fake();
    }

    #[Test]
    public function testCompleteWorkflowForExcessiveWorkingHours(): void
    {
        // Step 1: Create a shift that violates the daily max hours rule (10 hours instead of 8)
        $shiftDate = now()->addDays(1);

        $longShift = Shift::create([
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

        // Assign user to shift
        $longShift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Step 2: Run validation service
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        // Assert violation was created
        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertEquals($this->maxHoursRule->id, $violation->shift_rule_id);
        $this->assertEquals($this->user->id, $violation->user_id);
        $this->assertEquals($longShift->id, $violation->shift_id);
        $this->assertEquals('warning', $violation->severity);
        $this->assertEquals('active', $violation->status);

        // Check violation data
        $this->assertArrayHasKey('planned_hours', $violation->violation_data);
        $this->assertEquals(10, $violation->violation_data['planned_hours']);
        $this->assertEquals(8, $violation->violation_data['max_allowed']);

        // Step 3: Test violation exists in database
        $dbViolation = ShiftRuleViolation::where('shift_id', $longShift->id)->first();
        $this->assertNotNull($dbViolation);
        $this->assertEquals($this->maxHoursRule->id, $dbViolation->shift_rule_id);
    }

    #[Test]
    public function testConsecutiveWorkingDaysViolation(): void
    {
        // Create 6 consecutive days of work (violates 5-day rule)
        $startDate = now()->startOfWeek(); // Monday
        $shifts = [];

        for ($i = 0; $i < 6; $i++) {
            $shiftDate = $startDate->copy()->addDays($i);

            $event = \Artwork\Modules\Event\Models\Event::factory()->create([
                'name' => "Test Event Tag $i",
                'project_id' => $this->project->id,
                'room_id' => $this->room->id,
                'start_time' => $shiftDate->setTimeFromTimeString('09:00:00'),
                'end_time' => $shiftDate->setTimeFromTimeString('17:00:00')
            ]);

            $shift = Shift::create([
                'event_id' => $event->id,
                'start_date' => $shiftDate->format('Y-m-d'),
                'end_date' => $shiftDate->format('Y-m-d'),
                'start' => '09:00:00',
                'end' => '17:00:00', // 8 hours
                'break_minutes' => 60,
                'craft_id' => $this->craft->id,
                'description' => "Schicht Tag $i",
                'is_committed' => false
            ]);

            $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);
            $shifts[] = $shift;
        }

        // Run validation for the week
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $startDate,
            $startDate->copy()->addDays(6)
        );

        // Should have violation on 6th day
        $consecutiveViolations = $violations->where('shift_rule_id', $this->consecutiveDaysRule->id);
        $this->assertGreaterThan(0, $consecutiveViolations->count());

        $violation = $consecutiveViolations->first();
        $this->assertArrayHasKey('consecutive_days', $violation->violation_data);
        $this->assertEquals(6, $violation->violation_data['consecutive_days']);
        $this->assertEquals(5, $violation->violation_data['max_allowed']);
    }

    #[Test]
    public function testCommandValidationWorkflow(): void
    {
        // Create violation scenario
        $shiftDate = now()->addDays(2);

        $shift = Shift::create([
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

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run the validation command
        $exitCode = Artisan::call('shift-rules:validate', ['--days' => 7]);

        // Command should complete successfully
        $this->assertEquals(0, $exitCode);

        // Check that violation was created
        $violations = ShiftRuleViolation::where('shift_id', $shift->id)->get();
        $this->assertGreaterThan(0, $violations->count());

        $violation = $violations->first();
        $this->assertEquals($this->maxHoursRule->id, $violation->shift_rule_id);
        $this->assertEquals(14, $violation->violation_data['planned_hours']);
    }

    #[Test]
    public function testViolationResolutionWorkflow(): void
    {
        // Create a violation
        $shiftDate = now()->addDays(3);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '19:00:00', // 11 hours
            'break_minutes' => 60,
            'craft_id' => $this->craft->id,
            'description' => 'Lange Schicht',
            'is_committed' => false
        ]);

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Create violation through service
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        $violation = $violations->first();
        $this->assertEquals('active', $violation->status);

        // Test resolution
        $violation->resolve($this->user->id);
        $violation->refresh();

        $this->assertEquals('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by);

        // Test ignore functionality
        $violation->update(['status' => 'active', 'resolved_at' => null, 'resolved_by' => null]);
        $violation->ignore($this->user->id);
        $violation->refresh();

        $this->assertEquals('ignored', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by);
    }

    #[Test]
    public function testMultipleRulesAndMultipleViolations(): void
    {
        // Create scenario that violates both rules
        $startDate = now()->startOfWeek();

        // Day 1-5: Normal 8h shifts (OK)
        for ($i = 0; $i < 5; $i++) {
            $shiftDate = $startDate->copy()->addDays($i);

            $event = \Artwork\Modules\Event\Models\Event::factory()->create([
                'name' => "Normal Event Tag $i",
                'project_id' => $this->project->id,
                'room_id' => $this->room->id
            ]);

            $shift = Shift::create([
                'event_id' => $event->id,
                'start_date' => $shiftDate->format('Y-m-d'),
                'end_date' => $shiftDate->format('Y-m-d'),
                'start' => '09:00:00',
                'end' => '17:00:00', // 8 hours
                'break_minutes' => 0,
                'craft_id' => $this->craft->id,
                'description' => "Normale Schicht Tag $i",
                'is_committed' => false
            ]);

            $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);
        }

        // Day 6: Long shift (12 hours) + 6th consecutive day
        $violationDate = $startDate->copy()->addDays(5);

        $violationEvent = \Artwork\Modules\Event\Models\Event::factory()->create([
            'name' => 'Violation Event',
            'project_id' => $this->project->id,
            'room_id' => $this->room->id
        ]);

        $violationShift = Shift::create([
            'event_id' => $violationEvent->id,
            'start_date' => $violationDate->format('Y-m-d'),
            'end_date' => $violationDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '20:00:00', // 12 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Doppelt problematische Schicht',
            'is_committed' => false
        ]);

        $violationShift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $startDate,
            $startDate->copy()->addDays(6)
        );

        // Should have violations for both rules on the same day
        $violationsOnLastDay = $violations->filter(function ($violation) use ($violationDate) {
            return $violation->violation_date->format('Y-m-d') === $violationDate->format('Y-m-d');
        });
        
        $this->assertGreaterThan(1, $violationsOnLastDay->count());

        // Check specific violations
        $maxHoursViolation = $violationsOnLastDay->where('shift_rule_id', $this->maxHoursRule->id)->first();
        $consecutiveViolation = $violationsOnLastDay->where('shift_rule_id', $this->consecutiveDaysRule->id)->first();

        $this->assertNotNull($maxHoursViolation);
        $this->assertNotNull($consecutiveViolation);

        // Verify violation data
        $this->assertEquals(12, $maxHoursViolation->violation_data['planned_hours']);
        $this->assertEquals(6, $consecutiveViolation->violation_data['consecutive_days']);
    }

    #[Test]
    public function testNoViolationWhenRulesAreRespected(): void
    {
        // Create compliant shift (7 hours, well within 8-hour limit)
        $shiftDate = now()->addDays(1);

        $shift = Shift::create([
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

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        // Should have no violations
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testInactiveRulesAreIgnored(): void
    {
        // Deactivate the max hours rule
        $this->maxHoursRule->update(['is_active' => false]);

        // Create violating shift (10 hours)
        $shiftDate = now()->addDays(1);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00', // 10 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Lange Schicht',
            'is_committed' => false
        ]);

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        // Should have no violations because rule is inactive
        $maxHoursViolations = $violations->where('shift_rule_id', $this->maxHoursRule->id);
        $this->assertCount(0, $maxHoursViolations);
    }

    #[Test]
    public function testUserWithoutActiveContractHasNoViolations(): void
    {
        // Create user without active contract
        $userWithoutContract = \Artwork\Modules\User\Models\User::factory()->create([
            'first_name' => 'Lisa',
            'last_name' => 'Musterfrau'
        ]);

        // Create violating shift for this user
        $shiftDate = now()->addDays(1);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '06:00:00',
            'end' => '22:00:00', // 16 hours - massive violation
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Extrem lange Schicht',
            'is_committed' => false
        ]);

        $shift->users()->attach($userWithoutContract->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $userWithoutContract,
            $shiftDate,
            $shiftDate
        );

        // Should have no violations because user has no active contract
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testViolationDisplaysCorrectWarningColor(): void
    {
        // Create violation
        $shiftDate = now()->addDays(1);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '18:00:00', // 10 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Test Schicht',
            'is_committed' => false
        ]);

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Run validation
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            $shiftDate,
            $shiftDate
        );

        $violation = $violations->first();
        $this->assertEquals('#ff0000', $violation->getWarningColor());
        $this->assertStringContainsString('Niemand soll mehr als 8 Stunden', $violation->getViolationMessage());
    }

    #[Test]
    public function testCommandOutputIncludesViolationDetails(): void
    {
        // Create violation scenario
        $shiftDate = now()->addDays(1);

        $shift = Shift::create([
            'event_id' => $this->event->id,
            'start_date' => $shiftDate->format('Y-m-d'),
            'end_date' => $shiftDate->format('Y-m-d'),
            'start' => '08:00:00',
            'end' => '20:00:00', // 12 hours
            'break_minutes' => 0,
            'craft_id' => $this->craft->id,
            'description' => 'Lange Test Schicht',
            'is_committed' => false
        ]);

        $shift->users()->attach($this->user->id, ['shift_qualification_id' => $this->shiftQualification->id]);

        // Capture command output
        Artisan::call('shift-rules:validate', ['--days' => 7]);
        $output = Artisan::output();

        // Check that output contains violation information
        $this->assertStringContainsString('Found', $output);
        $this->assertStringContainsString('rule violations', $output);
        $this->assertStringContainsString('Max Mustermann', $output);
        $this->assertStringContainsString('Maximal 8 Stunden pro Tag', $output);
    }
}
