<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Workflow\Exceptions\UnknownActionException;
use Illuminate\Support\Collection;

class ActionResolver
{
    public function __construct(
        private Collection $actions = new Collection()
    ) {
        $this->registerDefaultActions();
    }

    public function register(WorkflowAction $action): self
    {
        $this->actions->put($action->getName(), $action);
        return $this;
    }

    public function resolve(string $actionName): WorkflowAction
    {
        if (!$this->actions->has($actionName)) {
            throw new UnknownActionException("Unknown action: {$actionName}");
        }

        return $this->actions->get($actionName);
    }

    public function getAvailableActions(): array
    {
        return $this->actions->keys()->toArray();
    }

    private function registerDefaultActions(): void
    {
        $this->register(app(NotificationAction::class));
        $this->register(app(UpdateDataAction::class));
        $this->register(app(ShiftRuleNotificationAction::class));
    }
}
