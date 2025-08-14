<?php

namespace Database\Seeders;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Illuminate\Database\Seeder;

class SimpleViolationTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Setting up simple violation test...');

        // 1. Get first user and contract
        $user = User::first();
        $contract = UserContract::first();
        
        if (!$user || !$contract) {
            $this->command->error('Missing user or contract data');
            return;
        }

        // 2. Assign contract to user if not already assigned
        if (!UserContractAssign::where('user_id', $user->id)->where('user_contract_id', $contract->id)->exists()) {
            UserContractAssign::create([
                'user_id' => $user->id,
                'user_contract_id' => $contract->id
            ]);
            $this->command->info("✅ Assigned contract {$contract->id} to user {$user->first_name} {$user->last_name}");
        }

        // 3. Create test rule
        $rule = ShiftRule::firstOrCreate(
            ['name' => 'Test: Max 8h/Tag'],
            [
                'description' => 'Test-Regel für max. 8 Stunden pro Tag',
                'trigger_type' => 'maxWorkingHoursOnDay',
                'individual_number_value' => 8.0,
                'warning_color' => '#ff0000',
                'is_active' => true
            ]
        );

        // 4. Assign rule to contract
        if (!$rule->contracts()->where('contract_id', $contract->id)->exists()) {
            $rule->contracts()->attach($contract->id);
            $this->command->info('✅ Assigned rule to contract');
        }

        $this->command->info('');
        $this->command->info('✅ Setup completed!');
        $this->command->info("User: {$user->first_name} {$user->last_name} (ID: {$user->id})");
        $this->command->info("Contract: ID {$contract->id}");
        $this->command->info("Rule: {$rule->name}");
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('1. Create a shift with >8 hours for this user');
        $this->command->info('2. Run: ddev artisan shift-rules:validate --days=7');
    }
}