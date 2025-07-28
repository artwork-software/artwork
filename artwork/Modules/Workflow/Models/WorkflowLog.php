<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowLog extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'transition',
        'from_place',
        'to_place',
        'triggered_at',
        'metadata'
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }
}
