<?php

namespace Artwork\Modules\Workflow\Console\Commands;

use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Illuminate\Console\Command;

class WorkflowManagementCommand extends Command
{
    protected $signature = 'workflow:manage
                            {action : Action to perform (list|show|transition)}
                            {--id= : Workflow instance ID}
                            {--transition= : Transition name to execute}
                            {--type= : Filter by workflow type}';

    protected $description = 'Manage workflow instances and execute transitions';

    public function __construct(
        private readonly WorkflowService $workflowService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listWorkflows(),
            'show' => $this->showWorkflow(),
            'transition' => $this->executeTransition(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    private function listWorkflows(): int
    {
        $type = $this->option('type');
        
        $query = WorkflowInstance::with(['workflowDefinitionConfig.workflowDefinition', 'subject'])
            ->whereNull('completed_at');

        if ($type) {
            $query->whereHas('workflowDefinitionConfig.workflowDefinition', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        $instances = $query->get();

        if ($instances->isEmpty()) {
            $this->info('No active workflow instances found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Type', 'Subject', 'Current Place', 'Started At'],
            $instances->map(function ($instance) {
                return [
                    $instance->id,
                    $instance->workflowDefinitionConfig->workflowDefinition->type,
                    get_class($instance->subject) . ' #' . $instance->subject->id,
                    $instance->current_place,
                    $instance->created_at->format('Y-m-d H:i:s')
                ];
            })
        );

        return self::SUCCESS;
    }

    private function showWorkflow(): int
    {
        $id = $this->option('id');
        
        if (!$id) {
            $this->error('Please provide workflow instance ID with --id option');
            return self::FAILURE;
        }

        $instance = WorkflowInstance::with(['workflowDefinitionConfig.workflowDefinition', 'subject'])
            ->find($id);

        if (!$instance) {
            $this->error("Workflow instance {$id} not found");
            return self::FAILURE;
        }

        $this->info("Workflow Instance #{$instance->id}");
        $this->info("Type: " . $instance->workflowDefinitionConfig->workflowDefinition->type);
        $this->info("Subject: " . get_class($instance->subject) . " #{$instance->subject->id}");
        $this->info("Current Place: {$instance->current_place}");
        $this->info("Started: " . $instance->created_at->format('Y-m-d H:i:s'));
        
        if ($instance->completed_at) {
            $this->info("Completed: " . $instance->completed_at->format('Y-m-d H:i:s'));
        }

        // Show available transitions
        $transitions = $this->workflowService->getAvailableTransitions($instance);
        
        if (!empty($transitions)) {
            $this->info("\nAvailable Transitions:");
            foreach ($transitions as $transition) {
                $this->info("- {$transition['name']}: {$transition['label']}");
            }
        } else {
            $this->info("\nNo available transitions.");
        }

        return self::SUCCESS;
    }

    private function executeTransition(): int
    {
        $id = $this->option('id');
        $transitionName = $this->option('transition');
        
        if (!$id || !$transitionName) {
            $this->error('Please provide both --id and --transition options');
            return self::FAILURE;
        }

        $instance = WorkflowInstance::find($id);
        
        if (!$instance) {
            $this->error("Workflow instance {$id} not found");
            return self::FAILURE;
        }

        $success = $this->workflowService->executeTransition($instance, $transitionName);
        
        if ($success) {
            $this->info("Transition '{$transitionName}' executed successfully");
            $this->info("New state: {$instance->fresh()->current_place}");
        } else {
            $this->error("Failed to execute transition '{$transitionName}'");
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}