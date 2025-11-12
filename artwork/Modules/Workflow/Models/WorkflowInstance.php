<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_definition_config_id',
        'subject_type',
        'subject_id',
        'current_place',
        'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime'
    ];

    public function workflowDefinitionConfig(): BelongsTo
    {
        return $this->belongsTo(
            WorkflowDefinitionConfig::class,
            'workflow_definition_config_id',
            'id',
            'workflowDefinitionConfig'
        );
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function workflowInstanceData(): HasMany
    {
        return $this->hasMany(WorkflowInstanceData::class);
    }

    public function currentData(): HasOne
    {
        return $this->hasOne(WorkflowInstanceData::class)
            ->whereNull('deprecated_at')
            ->latest('created_at');
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isRunnable(): bool
    {
        return !$this->isCompleted() && $this->workflowDefinitionConfig->isActive();
    }

    public function saveData(array $data): WorkflowInstanceData
    {
        // Deprecate current data if exists
        if ($this->currentData) {
            $this->currentData->update(['deprecated_at' => now()]);
        }

        return $this->workflowInstanceData()->create([
            'data' => $data
        ]);
    }

    public function getData(): array
    {
        return $this->currentData?->data ?? [];
    }

    public function transitionTo(string $place, ?string $transition = null): void
    {
        $this->update(['current_place' => $place]);

        $this->workflowLogs()->create([
            'transition' => $transition,
            'from_place' => $this->getOriginal('current_place'),
            'to_place' => $place,
            'triggered_at' => now()
        ]);
    }

    public function complete(): void
    {
        $this->update(['completed_at' => now()]);
    }
}
