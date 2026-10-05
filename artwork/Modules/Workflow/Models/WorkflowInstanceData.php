<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstanceData extends Model
{
    protected $table = 'workflow_instance_data';

    protected $fillable = [
        'workflow_instance_id',
        'data',
        'deprecated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'deprecated_at' => 'datetime'
    ];

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id', 'id', 'workflowInstance');
    }

    public function isActive(): bool
    {
        return $this->deprecated_at === null;
    }
}
