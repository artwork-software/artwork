<?php

namespace Artwork\Modules\Workflow\Traits;

use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWorkflows
{
    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'subject');
    }
    
    public function getActiveWorkflows(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->workflowInstances()
            ->whereNull('completed_at')
            ->with(['workflowDefinitionConfig.workflowDefinition'])
            ->get();
    }
    
    public function hasActiveWorkflow(string $workflowType = null): bool
    {
        $query = $this->workflowInstances()->whereNull('completed_at');
        
        if ($workflowType) {
            $query->whereHas('workflowDefinitionConfig.workflowDefinition', function ($q) use ($workflowType) {
                $q->where('type', $workflowType);
            });
        }
        
        return $query->exists();
    }
    
    public function getWorkflowSubjectInfo(): array
    {
        return [
            'id' => $this->id,
            'type' => static::class,
            'title' => $this->title ?? $this->name ?? "#{$this->id}",
            'url' => method_exists($this, 'getUrl') ? $this->getUrl() : null
        ];
    }
    
    public function canHaveWorkflow(string $workflowType): bool
    {
        // Override in models to add specific logic
        return true;
    }
}
