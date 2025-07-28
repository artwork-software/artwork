<?php

namespace Artwork\Modules\Workflow\Contracts;

use Illuminate\Database\Eloquent\Model;

interface WorkflowRule
{
    public function getName(): string;
    
    public function getDescription(): string;
    
    public function validate(Model $subject, array $context = []): array;
    
    public function canApplyTo(Model $subject): bool;
    
    public function getConfiguration(): array;
}
