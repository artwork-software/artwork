<?php

namespace Artwork\Modules\Workflow\Services;

use Artwork\Modules\Workflow\Actions\ActionResolver;
use Artwork\Modules\Workflow\Contracts\WorkflowSubject;
use Artwork\Modules\Workflow\Exceptions\InvalidWorkflowConfigException;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Repositories\WorkflowDefinitionRepository;
use Artwork\Modules\Workflow\Repositories\WorkflowInstanceRepository;
use Illuminate\Database\Eloquent\Model;

class WorkflowService
{
    public function __construct(
        private readonly WorkflowDefinitionRepository $workflowDefinitionRepository,
        private readonly WorkflowInstanceRepository $workflowInstanceRepository,
        private readonly ActionResolver $actionResolver
    ) {
    }

    public function createDefinition(string $name, string $type, array $config): WorkflowDefinition
    {
        $this->validateConfig($config);

        $definition = $this->workflowDefinitionRepository->create([
            'name' => $name,
            'type' => $type,
            'is_active' => true
        ]);

        $definition->saveConfig($config);

        return $definition;
    }

    public function startWorkflow(WorkflowDefinition $definition, WorkflowSubject|Model $subject): WorkflowInstance
    {
        if (!$definition->isRunnable()) {
            throw new InvalidWorkflowConfigException('Workflow definition is not runnable');
        }

        if ($definition->hasReachedMaxInstances()) {
            throw new InvalidWorkflowConfigException('Maximum instances reached for this workflow');
        }

        $config = $definition->currentConfig;
        $initialPlace = $config->getInitialPlace();

        return $this->workflowInstanceRepository->create([
            'workflow_definition_config_id' => $config->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'current_place' => $initialPlace
        ]);
    }

    public function executeTransition(WorkflowInstance $instance, string $transition): bool
    {
        if (!$instance->isRunnable()) {
            return false;
        }

        $config = $instance->workflowDefinitionConfig;
        $transitions = $config->getTransitions();

        $transitionConfig = collect($transitions)->firstWhere('name', $transition);
        if (!$transitionConfig) {
            return false;
        }

        // Check if transition is valid from current place
        $from = $transitionConfig['from'] ?? [];
        if (!in_array($instance->current_place, $from)) {
            return false;
        }

        $toPlace = $transitionConfig['to'];

        // Execute actions before transition
        $this->executeActions($instance, $transitionConfig['actions'] ?? []);

        // Perform transition
        $instance->transitionTo($toPlace, $transition);

        // Check if workflow is completed
        $places = $config->getPlaces();
        $placeConfig = collect($places)->firstWhere('name', $toPlace);
        if ($placeConfig && ($placeConfig['type'] ?? null) === 'end') {
            $instance->complete();
        }

        return true;
    }

    public function getAvailableTransitions(WorkflowInstance $instance): array
    {
        if (!$instance->isRunnable()) {
            return [];
        }

        $config = $instance->workflowDefinitionConfig;
        $transitions = $config->getTransitions();

        return collect($transitions)
            ->filter(fn($t) => in_array($instance->current_place, $t['from'] ?? []))
            ->values()
            ->toArray();
    }

    private function executeActions(WorkflowInstance $instance, array $actions): void
    {
        foreach ($actions as $actionConfig) {
            $actionName = $actionConfig['action'] ?? null;
            $parameters = $actionConfig['parameters'] ?? [];

            if (!$actionName) {
                continue;
            }

            try {
                $action = $this->actionResolver->resolve($actionName);
                if ($action->canExecute($instance, $parameters)) {
                    $action->execute($instance, $parameters);
                }
            } catch (\Exception $e) {
                // Log error but continue workflow
                logger()->error('Error executing workflow action', [
                    'action' => $actionName,
                    'workflow_instance_id' => $instance->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function validateConfig(array $config): void
    {
        $required = ['places', 'transitions', 'initial_place'];

        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new InvalidWorkflowConfigException("Missing required config key: {$key}");
            }
        }

        if (empty($config['places']) || empty($config['transitions'])) {
            throw new InvalidWorkflowConfigException('Places and transitions cannot be empty');
        }

        // Validate initial place exists
        $placeNames = collect($config['places'])->pluck('name')->toArray();
        if (!in_array($config['initial_place'], $placeNames)) {
            throw new InvalidWorkflowConfigException('Initial place not found in places');
        }
    }
}
