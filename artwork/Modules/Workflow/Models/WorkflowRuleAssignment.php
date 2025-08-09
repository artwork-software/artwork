<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowRuleAssignment extends Model
{
    protected $fillable = [
        'workflow_rule_id',
        'subject_type',
        'subject_id',
        'assigned_at',
        'assigned_by'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    public function workflowRule(): BelongsTo
    {
        return $this->belongsTo(WorkflowRule::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
