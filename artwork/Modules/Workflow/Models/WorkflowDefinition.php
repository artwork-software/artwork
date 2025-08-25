<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowDefinition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'max_instances'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_instances' => 'integer'
    ];

    public function workflowDefinitionConfigs(): HasMany
    {
        return $this->hasMany(WorkflowDefinitionConfig::class);
    }

    public function currentConfig(): HasOne
    {
        return $this->hasOne(WorkflowDefinitionConfig::class)
            ->whereNull('deprecated_at')
            ->latest('created_at');
    }

    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function saveConfig(array $config): WorkflowDefinitionConfig
    {
        // Deprecate current config if exists
        if ($this->currentConfig) {
            $this->currentConfig->update(['deprecated_at' => now()]);
        }

        return $this->workflowDefinitionConfigs()->create([
            'config' => $config
        ]);
    }

    public function isRunnable(): bool
    {
        return $this->is_active && $this->currentConfig !== null;
    }

    public function hasReachedMaxInstances(): bool
    {
        if ($this->max_instances === null) {
            return false;
        }

        return $this->workflowInstances()
            ->whereNull('completed_at')
            ->count() >= $this->max_instances;
    }
}
