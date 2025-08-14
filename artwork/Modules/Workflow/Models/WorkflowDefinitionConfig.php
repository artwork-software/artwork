<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinitionConfig extends Model
{
    protected $fillable = [
        'workflow_definition_id',
        'config',
        'deprecated_at'
    ];

    protected $casts = [
        'config' => 'array',
        'deprecated_at' => 'datetime'
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id', 'id', 'workflowDefinition');
    }

    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function isActive(): bool
    {
        return $this->deprecated_at === null;
    }

    public function getPlaces(): array
    {
        return $this->config['places'] ?? [];
    }

    public function getTransitions(): array
    {
        return $this->config['transitions'] ?? [];
    }

    public function getActions(): array
    {
        return $this->config['actions'] ?? [];
    }

    public function getInitialPlace(): ?string
    {
        return $this->config['initial_place'] ?? null;
    }
}
