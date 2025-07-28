<?php

namespace Artwork\Modules\Workflow\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $subject,
        public readonly string $triggerType,
        public readonly array $context = []
    ) {}
}
