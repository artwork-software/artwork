<?php

namespace Artwork\Modules\Workflow\Traits;

use Artwork\Modules\Workflow\Events\WorkflowTriggered;

trait TriggersWorkflows
{
    public static function bootTriggersWorkflows(): void
    {
        static::created(function ($model) {
            $model->triggerWorkflow('created');
        });

        static::updated(function ($model) {
            $model->triggerWorkflow('updated', [
                'changes' => $model->getChanges(),
                'original' => $model->getOriginal()
            ]);
        });

        static::deleted(function ($model) {
            $model->triggerWorkflow('deleted');
        });
    }

    public function triggerWorkflow(string $triggerType, array $context = []): void
    {
        event(new WorkflowTriggered($this, $triggerType, $context));
    }

    public function triggerCustomWorkflow(string $triggerType, array $context = []): void
    {
        $this->triggerWorkflow($triggerType, $context);
    }
}
