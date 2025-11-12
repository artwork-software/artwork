<?php

namespace Database\Seeders;

use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowDefinitionConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CompleteShiftRulesWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Load the workflow definition from JSON file
        $workflowPath = base_path('artwork/Modules/Workflow/Examples/complete_shift_rules_workflow.json');
        
        if (!File::exists($workflowPath)) {
            $this->command->error('Workflow definition file not found: ' . $workflowPath);
            return;
        }

        $workflowData = json_decode(File::get($workflowPath), true);

        if (!$workflowData) {
            $this->command->error('Could not parse workflow definition JSON file');
            return;
        }

        // Create or update the workflow definition
        $workflow = WorkflowDefinition::updateOrCreate(
            [
                'type' => $workflowData['type']
            ],
            [
                'name' => $workflowData['name'],
                'description' => $workflowData['description'],
                'is_active' => true,
            ]
        );

        // Create or update the workflow configuration
        WorkflowDefinitionConfig::updateOrCreate(
            [
                'workflow_definition_id' => $workflow->id
            ],
            [
                'config' => json_encode([
                    'initial_place' => $workflowData['initial_place'],
                    'places' => $workflowData['places'],
                    'transitions' => $workflowData['transitions'],
                    'variables' => $workflowData['variables'],
                    'rule_types' => $workflowData['rule_types'],
                    'notification_templates' => $workflowData['notification_templates'],
                    'automation' => $workflowData['automation']
                ])
            ]
        );

        $this->command->info('Complete Shift Rules Workflow seeded successfully');
        $this->command->info('Workflow ID: ' . $workflow->id);
        $this->command->info('Workflow Type: ' . $workflow->type);
    }
}