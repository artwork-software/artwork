<?php

namespace Database\Seeders;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Seeder;

class ShiftRuleViolationTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating test data for ShiftRule violations...');

        // 1. Create a test rule
        $rule = ShiftRule::create([
            'name' => 'Test: Maximale Arbeitsstunden pro Tag',
            'description' => 'Testet ob mehr als 8 Stunden pro Tag geplant sind',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff6b6b',
            'is_active' => true
        ]);

        $this->command->info('✅ Created ShiftRule: ' . $rule->name);

        // 2. Find or create a test user with contract
        $user = User::first();
        if (!$user) {
            $this->command->error('❌ No users found. Please create a user first.');
            return;
        }

        $contract = $user->activeWorkContract();
        if (!$contract) {
            $this->command->error('❌ User has no active contract. Please assign a UserContract.');
            return;
        }

        // 3. Assign rule to contract
        if (!$rule->contracts()->where('contract_id', $contract->id)->exists()) {
            $rule->contracts()->attach($contract->id);
            $this->command->info('✅ Assigned rule to contract: ' . $contract->id);
        }

        // 4. Create test event
        $event = Event::create([
            'name' => 'Test Event für Violation',
            'start_time' => now()->addDay()->setTime(8, 0),
            'end_time' => now()->addDay()->setTime(20, 0),
            'room_id' => 1, // Assuming room exists
            'project_id' => 1, // Assuming project exists
            'user_id' => $user->id,
            'eventName' => 'Test Event',
            'audience' => 0,
            'is_loud' => false,
        ]);

        $this->command->info('✅ Created test event: ' . $event->name);

        // 5. Create craft if needed
        $craft = Craft::first();
        if (!$craft) {
            $craft = Craft::create([
                'name' => 'Test Craft',
                'abbreviation' => 'TC',
                'color' => '#3b82f6'
            ]);
        }

        // 6. Create a shift that should trigger a violation (more than 8 hours)
        $shift = Shift::create([
            'event_id' => $event->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'start' => '08:00',
            'end' => '20:00',        // 12 hours - should trigger violation!
            'break_minutes' => 60,    // 1 hour break = 11 working hours
            'craft_id' => $craft->id,
            'description' => 'Test Schicht für Violation (11 Arbeitsstunden)',
            'is_committed' => false,
        ]);

        // 7. Assign user to shift
        $shift->users()->attach($user->id);

        $this->command->info('✅ Created test shift with violation:');
        $this->command->info("   - Start: {$shift->start_date} {$shift->start}");
        $this->command->info("   - End: {$shift->end_date} {$shift->end}");
        $this->command->info("   - Working hours: 11 (should exceed 8h limit)");
        $this->command->info("   - Assigned to user: {$user->first_name} {$user->last_name}");

        $this->command->info('');
        $this->command->info('🚀 Test data created successfully!');
        $this->command->info('');
        $this->command->info('Now run: ddev artisan shift-rules:validate --days=7');
        $this->command->info('Expected result: 1 rule violation should be found');
    }
}