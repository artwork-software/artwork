<?php

namespace Artwork\Modules\Workflow\Jobs;

use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWorkflowJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $workflowInstanceId,
        private readonly string $transition,
        private readonly array $parameters = []
    ) {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowService $workflowService): void
    {
        $instance = WorkflowInstance::find($this->workflowInstanceId);

        if (!$instance || !$instance->isRunnable()) {
            return;
        }

        try {
            $workflowService->executeTransition($instance, $this->transition);
        } catch (\Exception $e) {
            logger()->error('Workflow job failed', [
                'workflow_instance_id' => $this->workflowInstanceId,
                'transition' => $this->transition,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Workflow job permanently failed', [
            'workflow_instance_id' => $this->workflowInstanceId,
            'transition' => $this->transition,
            'error' => $exception->getMessage()
        ]);
    }
}
